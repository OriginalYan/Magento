<?php
/**
 * Zitec_Dpd – shipping carrier extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @copyright  Copyright (c) 2014 Zitec COM
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Zitec\Dpd\Model\Shipping\Carrier;

use Magento\Framework\Exception\LocalizedException;
use Zitec\Dpd\Model\Observer\DpdCreateShipment;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Dpd extends \Zitec\Dpd\Model\Shipping\Carrier\AbstractCarrier implements \Zitec\Dpd\Model\Carrier\CarrierInterface
{
    const CARRIER_CODE = 'zitecDpd';
    protected $_code = 'zitecDpd';

    /**
     * @var \Zitec\Dpd\Model\Config\Source\Service
     */
    protected $dpdConfigSourceService;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $shippingRateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $quoteQuoteAddressRateResultMethodFactory;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Carrier\TablerateFactory
     */
    protected $dpdMysql4CarrierTablerate;

    /**
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    protected $dpdPostcodeSearchHelper;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory
     */
    protected $salesResourceModelOrderShipmentTrackCollectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $salesOrderShipmentFactory;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory
     */
    protected $dpdMysql4DpdShipCollectionFactory;

    /**
     * @var \Zitec\Dpd\Helper\ApiFactory
     */
    protected $dpdApiFactory;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;
    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    private $dpdHelperWs;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Zitec\Dpd\Model\Observer\DpdCreateShipment
     */
    private $dpdCreateShipment;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,

        \Zitec\Dpd\Model\Config\Source\Service $dpdConfigSourceService,
        \Magento\Shipping\Model\Rate\ResultFactory $shippingRateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $quoteQuoteAddressRateResultMethodFactory,
        \Magento\Tax\Helper\Data $taxHelper,
        \Zitec\Dpd\Model\Mysql4\Carrier\Tablerate $dpdMysql4CarrierTablerate,
        \Zitec\Dpd\Helper\Postcode\Search $dpdPostcodeSearchHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Helper\Ws $dpdHelperWs,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $salesResourceModelOrderShipmentTrackCollectionFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $salesOrderShipmentFactory,
        \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory $dpdMysql4DpdShipCollectionFactory,
        \Zitec\Dpd\Helper\ApiFactory $dpdApiFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        DpdCreateShipment $dpdCreateShipment,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger);

        $this->dpdApiFactory = $dpdApiFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->dpdConfigSourceService = $dpdConfigSourceService;
        $this->shippingRateResultFactory = $shippingRateResultFactory;
        $this->quoteQuoteAddressRateResultMethodFactory = $quoteQuoteAddressRateResultMethodFactory;
        $this->taxHelper = $taxHelper;
        $this->dpdMysql4CarrierTablerate = $dpdMysql4CarrierTablerate;
        $this->dpdPostcodeSearchHelper = $dpdPostcodeSearchHelper;
        $this->dpdHelper = $dpdHelper;
        $this->dpdHelperWs = $dpdHelperWs;
        $this->salesResourceModelOrderShipmentTrackCollectionFactory = $salesResourceModelOrderShipmentTrackCollectionFactory;
        $this->salesOrderShipmentFactory = $salesOrderShipmentFactory;
        $this->dpdMysql4DpdShipCollectionFactory = $dpdMysql4DpdShipCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->dpdCreateShipment = $dpdCreateShipment;
        $this->request = $request;
    }
    public function getAllowedMethods()
    {
        return $this->dpdConfigSourceService->getAvailableServices();
    }

    /**
     * Collect and get rates
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return \Magento\Shipping\Model\Rate\Result|bool|null
     */
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
        $city = $shippingAddress->getCity();

        if (!$this->_canCollectRates($request)) {
            return false;
        }

        // Recalculate the package value excluding any virtual products.
        if (!$this->getConfigFlag('include_virtual_price')) {
            $request->setPackageValue($request->getPackagePhysicalValue());
        }

        // Free shipping by qty
        $freeQty           = 0;
        $totalPriceInclTax = 0;
        $totalPriceExclTax = 0;
        foreach ($request->getAllItems() as $item) {
            $totalPriceInclTax += $item->getBaseRowTotalInclTax();
            $totalPriceExclTax += $item->getBaseRowTotal();

            if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                        $freeQty += $item->getQty() * ($child->getQty() - (is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0));
                    }
                }
            } elseif ($item->getFreeShipping()) {
                $freeQty += ($item->getQty() - (is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0));
            }
        }

        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty    = $request->getPackageQty();
        $oldPrice  = $request->getPackageValue();

        $request->setPackageWeight($request->getFreeMethodWeight());
        $request->setPackageQty($oldQty - $freeQty);
        $request->setPackageValue($totalPriceInclTax);

        $this->_updateFreeMethodQuote($request);

        // The shipping price calculations for price vs destination is included.
        if ($this->_getTaxHelper()->shippingPriceIncludesTax($request->getStoreId())) {
            $request->setData('zitec_table_price', $totalPriceInclTax);
        } else {
            $request->setData('zitec_table_price', $totalPriceExclTax);
        }

        $rate = $this->getRate($request);

        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);
        $request->setPackageValue($oldPrice);

        $isFree               = false;
        $freeShippingPrice    = ($this->getConfigFlag('free_shipping_subtotal_tax_incl')) ? $totalPriceInclTax : $oldPrice;
        $freeShippingSubtotal = $this->getConfigData('free_shipping_subtotal');
        $freeShippingEnabled  = $this->getConfigFlag('free_shipping_enable');
        $freeShipping         = ($freeShippingEnabled && $freeShippingPrice >= $freeShippingSubtotal) ? true : false;
        if ($request->getFreeShipping() === true || $freeShipping) {
            $isFree = true;
        }

        $result = $this->shippingRateResultFactory->create();
        /* @var $result Mage_Shipping_Model_Rate_Result */

        if (count($rate) == 0) {
            return false;
        }

        $methods = array();
        foreach ($rate as $r) {
            // Before adding the rate, we check that it is active in the admin configuration.
            if (!$this->_isRateAllowedByAdminConfiguration($r)) {
                continue;
            }

            //There can be multiple rate the same method, but is first applicable.
            //If we have already considered a rate of this method, we again evaluate
            //(the other will be for weights / lower prices) or for more general conditions.

            $dpdMethod = $r['method'];
            if (in_array($dpdMethod, $methods)) {
                continue;
            }
            $methods[] = $dpdMethod;

            $method = $this->quoteQuoteAddressRateResultMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $price = $this->_calculateDPDPrice($r, $request);
            if ($price === false) {
                continue;
            }
            $method->setPrice($price);
            $method->setCost($price);
            $method->setMethod($dpdMethod);
            $methodDescriptions = $this->dpdConfigSourceService->getAvailableServices();
            $methodTitle        = $methodDescriptions[$dpdMethod];
            $method->setMethodTitle((string) $methodTitle);

            $result->append($method);
        }

        if ($isFree) {
            $cheapest = $result->getCheapestRate();
            if (!empty($cheapest)) {
                $cheapest->setPrice('0.00');
                $title = $cheapest->getMethodTitle() . ' (' . __('Free') . ')';
                $cheapest->setMethodTitle($title);
                $result->reset();
                $result->append($cheapest);
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return bool
     */
    protected function _canCollectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Exclude empty carts
        if (!$request->getAllItems()) {
            return false;
        }

        // Exclude carts containing products with no defined weight or where the
        // total weight of the cart is zero (virtual products only).
        if ($this->_cartContainsProductsOfZeroWeightOrWeighsNothing($request->getAllItems())) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return \Magento\Tax\Helper\Data
     */
    protected function _getTaxHelper()
    {
        return $this->taxHelper;
    }

    /**
     * Returns true if the cart contains items with no defined weight or the
     *  whole cart weighs nothing.
     *
     * @param array $itemsInCart
     *
     * @return boolean
     */
    protected function _cartContainsProductsOfZeroWeightOrWeighsNothing($itemsInCart)
    {

        if (!$itemsInCart) {
            return true;
        }

        $cartContainsNonVirtualItems = false;

        foreach ($itemsInCart as $item) {
            if ($item->getParentItem()) {
                continue;
            }


            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if (!$child->getProduct()->isVirtual()) {
                        if (!$child->getWeight() || ($child->getWeight() == 0)) {
                            return true;
                        }
                        $cartContainsNonVirtualItems = true;
                    }
                }
            } elseif (!$item->getProduct()->isVirtual()) {
                if (!$item->getWeight() || ($item->getWeight() == 0)) {
                    return true;
                }
                $cartContainsNonVirtualItems = true;
            }
        }

        if ($cartContainsNonVirtualItems) {
            return false;
        } else {
            return true; // All items in cart are virtual
        }
    }

    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return array
     */
    public function getRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $rates = $this->dpdMysql4CarrierTablerate->getRate($request);

        return $rates;
    }

    /**
     *
     * @param array $shippingRate
     *
     * @return boolean
     */
    protected function _isRateAllowedByAdminConfiguration($shippingRate)
    {

        $availableMethods = explode(',', $this->getconfigData('services'));

        return in_array($shippingRate['method'], $availableMethods);
    }

    /**
     * We calculate the shipping price based on the price / rate mentioned in
     * the rates table. If a "markup_type" (percent) indicated we travel to DPD WS
     * to calculate the final price based on the shipping cost with
     * his ws. If the price / percentage is less than zero indicates that the rate is not available.
     *
     * @param array                            $rate
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return array|boolean
     */
    protected function _calculateDPDPrice(array $rate, \Magento\Quote\Model\Quote\Address\RateRequest $request)
    {

        if (!$rate['markup_type']) {
            if ($rate['price'] >= 0) {
                return $rate['price'];
            } else {
                return false;
            }
        }

        $apiParams           = $this->dpdHelperWs->getShipmentParams($request->getStoreId());
        $apiParams['method'] = \Zitec_Dpd_Api_Configs::METHOD_SHIPMENT_CALCULATE_PRICE;

        try {
            $dpdApi         = $this->dpdApiFactory->create($apiParams);
            /** @var \Zitec_Dpd_Api_Shipment_CalculatePrice $calculatePrice */
            $calculatePrice = $dpdApi->getApiMethodObject();

            $postCode = $this->dpdPostcodeSearchHelper->extractPostCodeForShippingRequest($request);

            if (
                empty($request->getDestStreet())
                || empty($request->getDestCity())
                || empty($request->getDestCountryId())
            ) {
                return false;
            }
            $calculatePrice->setReceiverAddress($request->getDestStreet(), $request->getDestCity(), $postCode, $request->getDestCountryId())
                ->addParcel($request->getPackageWeight())
                ->setShipmentServiceCode($rate['method']);

            $insurance      = $this->dpdHelper->extractInsuranceValuesByRequest($request);
            $shouldSendInsuranceValue = $this->dpdHelper->getConfigData('send_insurance_value');
            if ($shouldSendInsuranceValue) {
                $calculatePrice = $calculatePrice->setAdditionalHighInsurance($insurance['goodsValue'], $insurance['currency'], $insurance['content']);
            }

            $calculatePrice->execute();
        } catch (\Exception $e) {
            $this->dpdHelper->log("An error occurred whilst calculating the DPD price for the shipment {$e->getMessage()}");

            return false;
        }

        $response = $calculatePrice->getCalculatePriceResponse();
        if ($response->hasError()) {
            $this->dpdHelper->log("DPD returned the following error whilst attempting to calculate the price of a shipment: {$response->getErrorText()}");

            return false;
        }


        if ($request->getBaseCurrency()->getCode() == $response->getCurrency()) {
            if ($this->_getTaxHelper()->shippingPriceIncludesTax($request->getStoreId())) {
                $dpdPrice = $response->getTotalAmount();
            } else {
                $dpdPrice = $response->getAmount();
            }
        } else if ($request->getBaseCurrency()->getCode() == $response->getCurrencyLocal()) {
            if ($this->_getTaxHelper()->shippingPriceIncludesTax($request->getStoreId())) {
                $dpdPrice = $response->getTotalAmountLocal();
            } else {
                $dpdPrice = $response->getAmountLocal();
            }
        } else {
            $this->dpdHelper->log("An error occurred whilst calculating the price of a shipment. The currency of the shipment ({$request->getBaseCurrency()->getCode()}) does not correspond to the currency ({$response->getCurrency()}) or the local currency ({$response->getCurrencyLocal()})  used by DPD. ");

            return false;
        }
        if ($rate['markup_type'] == 1) {
            return $dpdPrice * (1 + ($rate['price'] / 100));
        } else {
            return $dpdPrice + round(floatval($rate['price']), 2);
        }
    }

    public function getCitiesForPostcode($postcode, &$errorMsg)
    {

    }

    public function getPostcodesForCity($city, &$errorMsg)
    {

    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string                 $city
     * @param string                 $postcode
     * @param array                  $weightsPackages
     * @param string                 $errorStr
     *
     * @return double
     */
    public function getShippingCost(\Magento\Sales\Model\Order $order, $city, $postcode, $weightsPackages, &$errorStr)
    {
        $shippingAddress = $order->getShippingAddress();
        $city            = $city ? $city : $shippingAddress->getCity();
        $postcode        = $postcode ? $postcode : $shippingAddress->getPostcode();
        $serviceCode     = $this->dpdHelper->getDPDServiceCode($order->getShippingMethod());
        $street          = is_array($shippingAddress->getStreetFull()) ? implode("\n", $shippingAddress->getStreetFull()) : $shippingAddress->getStreetFull();

        $apiParams           = $this->dpdHelperWs->getShipmentParams($order->getStore());
        $apiParams['method'] = \Zitec_Dpd_Api_Configs::METHOD_SHIPMENT_CALCULATE_PRICE;


        try {
            $dpdApi         = $this->dpdApiFactory->create($apiParams);
            $calculatePrice = $dpdApi->getApiMethodObject();


            $calculatePrice->setReceiverAddress($street, $city, $postcode, $shippingAddress->getCountryId())
                ->setShipmentServiceCode($serviceCode);

            foreach ($weightsPackages as $parcelWeight) {
                $calculatePrice->addParcel($parcelWeight);
            }

            $insurance      = $this->dpdHelper->extractInsuranceValuesByOrder($order);
            $shouldSendInsuranceValue = $this->dpdHelper->getConfigData('send_insurance_value');
            if ($shouldSendInsuranceValue) {
                $calculatePrice = $calculatePrice->setAdditionalHighInsurance($insurance['goodsValue'], $insurance['currency'], $insurance['content']);
            }

            $calculatePrice->execute();
        } catch (\Exception $e) {
            $errorStr = __("Error obtaining shipping price: %1", $e->getMessage());
            $this->dpdHelper->log("An error occurred whilst calculating the DPD price for the shipment {$e->getMessage()}");

            return 0;
        }

        $response = $calculatePrice->getCalculatePriceResponse();
        if ($response->hasError()) {
            $errorStr = __("DPD error: %1", $response->getErrorText());
            $this->dpdHelper->log("DPD returned the following error whilst attempting to calculate the price of a shipment: {$response->getErrorText()}");

            return 0;
        }


        if ($order->getBaseCurrencyCode() == $response->getCurrency()) {
            return $response->getTotalAmount();
        } else if ($order->getBaseCurrencyCode() == $response->getCurrencyLocal()) {
            return $response->getTotalAmountLocal();
        } else {
            $errorStr = __("Shipping price not available in order currency");
            $this->dpdHelper->log("An error occurred whilst calculating the price of a shipment. The base currency of the shipment ({$order->getBaseCurrencyCode()}) does not correspond to the currency ({$response->getCurrency()}) or the local currency ({$response->getCurrencyLocal()})  used by DPD.");

            return 0;
        }
    }

    /**
     *
     * @return boolean
     */
    public function supportsCalculationOfShippingCosts()
    {
        return true;
    }

    public function getTrackingInfo($trackingNumber)
    {
        $trackingCollection = $this->salesResourceModelOrderShipmentTrackCollectionFactory->create();
        /* @var $trackingCollection Mage_Sales_Model_Mysql4_Order_Shipment_Track_Collection */
        $trackingCollection->addFieldToFilter('track_number', $trackingNumber);
        $track = $trackingCollection->getFirstItem();
        /* @var $track \Magento\Sales\Model\Order\Shipment\Track */
        if (!$track->getId()) {
            $result = array("title" => $this->getConfigData("title"), "number" => $trackingNumber);

            return $result;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $objectManager->get(\Magento\Sales\Model\Order::class);
        $order->load($track->getOrderId());

        /* @var $shipment \MageNTO\Sales\Model\Order\Shipment */
        $shipment     = $this->salesOrderShipmentFactory->create($order)
        ->load($track->getParentId());
        $carrierTitle = $this->getConfigData("title", $shipment->getStore());

        $ships = $this->dpdMysql4DpdShipCollectionFactory->create();
        /* @var $ships Zitec_Dpd_Model_Mysql4_Dpd_Ship_Collection */
        $ship = $ships->getByShipmentId($track->getParentId());
        /* @var $ship Zitec_Dpd_Model_Dpd_Ship */
        if (!$ship) {
            $errorMessage = __("Could not load the stored tracking information for track %1", $trackingNumber);
            $this->dpdHelper->log($errorMessage);
            $result = $this->_getTrackingInfoObject($trackingNumber, $carrierTitle, $errorMessage);

            return $result;
        }

        $response = @unserialize($ship->getSaveShipmentResponse());
        /* @var $response Zitec_Dpd_Api_Shipment_Save_Response */
        if (!$response) {
            $errorMessage = __("Error loading stored tracking information for track %1", $trackingNumber);
            $this->dpdHelper->log($errorMessage);
            $result = $this->_getTrackingInfoObject($trackingNumber, $carrierTitle, $errorMessage);

            return $result;
        }

        try {

            $statusResponse = $this->dpdHelperWs->getShipmentStatus($response);

        } catch (\Exception $e) {
            $errorMessage = __("Error calling DPD for track %1", $trackingNumber);
            $this->dpdHelper->log($errorMessage);
            $this->dpdHelper->log($e->getMessage());
            $result = $this->_getTrackingInfoObject($trackingNumber, $carrierTitle, $errorMessage);

            return $result;
        }

        if ($statusResponse->hasError()) {
            $errorMessage = __('Error calling DPD for track %1: %1 ', $trackingNumber, $statusResponse->getErrorText());
            $this->dpdHelper->log($errorMessage);
            $result = $this->_getTrackingInfoObject($trackingNumber, $carrierTitle, $errorMessage);

            return $result;
        }

        $result = $this->_getTrackingInfoObject($trackingNumber, $carrierTitle, "", $statusResponse);

        return $result;
    }

    /**
     *
     * @param string                                            $trackingNumber
     * @param string                                            $carrierTitle
     * @param string                                            $errorMessage
     * @param \Zitec_Dpd_Api_Shipment_GetShipmentStatus_Response $response
     *
     * @return \Varien_Object
     */
    protected function _getTrackingInfoObject($trackingNumber, $carrierTitle, $errorMessage, \Zitec_Dpd_Api_Shipment_GetShipmentStatus_Response $response = null)
    {
        $result = $result = $this->dataObjectFactory->create();
        $result->setTracking($trackingNumber);
        $result->setCarrierTitle($carrierTitle);
        $result->setErrorMessage($errorMessage);
        if ($response) {
            $result->setUrl($response->getTrackingUrl());
            $result->setDeliverydate($response->getDeliveryDate());
            $result->setDeliverytime($response->getDeliveryTime());
            $result->setShippedDate($response->getShipDate());
            $result->setService($response->getServiceDescription());
            $result->setWeight($response->getWeight());
        }

        return $result;
    }

    public function isValidCityPostcode($city, $postcode, &$errorMsg)
    {

    }

    public function shippingMethodRequiresShipmentsOfOnlyOneParcel($shippingMethod)
    {

    }

    public function supportsAddressValidation($countryId)
    {

    }

    /**
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function requestToShipment($request)
    {
        $packages = $request->getPackages();
        if (!is_array($packages) || !$packages) {
            throw new LocalizedException(__('No packages for request'));
        }

        /** @var \Zitec_Dpd_Api_Shipment_Save_Response $result */
        $result = $this->dpdCreateShipment->createShipment($request);

        $response = new \Magento\Framework\DataObject(
            [
                'info' => [
                    [
                        'tracking_number' => $result->getTrackingNumber(),
                        'label_content' => $result->getShippingLabelContent(),
                    ],
                ],
            ]
        );

        $request->setMasterTrackingId($result->getTrackingNumber());

        return $response;
    }

    public function isShippingLabelsAvailable()
    {
        return true;
        $shipmentId = $this->request->get('shipment_id');

        $shipCollection = $this->dpdMysql4DpdShipCollectionFactory->create();
        $ship = $shipCollection->getByShipmentId($shipmentId);

        return !$ship;
    }
}
