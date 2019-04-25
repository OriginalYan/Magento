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

namespace Zitec\Dpd\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * this class is used for
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Data extends AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $directoryCountryFactory;

    /**
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    protected $dpdPostcodeSearchHelper;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory
     */
    protected $dpdMysql4DpdShipCollectionFactory;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $configCountry;
    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;
    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    private $productMetadata;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CountryFactory $directoryCountryFactory,
        \Zitec\Dpd\Helper\Postcode\Search $dpdPostcodeSearchHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory $dpdMysql4DpdShipCollectionFactory,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Directory\Model\Config\Source\Country $configCountry,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->logger = $context->getLogger();
        $this->scopeConfig = $context->getScopeConfig();
        $this->directoryCountryFactory = $directoryCountryFactory;
        $this->dpdPostcodeSearchHelper = $dpdPostcodeSearchHelper;
        $this->messageManager = $messageManager;
        $this->dpdMysql4DpdShipCollectionFactory = $dpdMysql4DpdShipCollectionFactory;
        $this->taxConfig = $taxConfig;
        $this->backendHelper = $backendHelper;
        $this->configCountry = $configCountry;
        $this->appState = $appState;
        $this->productMetadata = $productMetadata;
    }



    /**
     * is used in checkout to extract the value of products
     * and the names
     *
     * @param $request
     *
     * @return array
     */
    public function extractInsuranceValuesByRequest($request)
    {
        $allItems    = $request->getAllItems();
        $description = '';
        $value       = 0;
        if (count($allItems)) {
            foreach ($allItems as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                $description .= ' | '.$item->getName();

                $value += $item->getRowTotalInclTax();
            }
        }
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return array(
            'goodsValue' => $value,
            'currency'   => $currencyCode,
            'content'    => $description
        );


    }

    /**
     * it is used in admin panel to process the products values and
     * products description
     *
     * @param $request
     *
     * @return array
     */
    public function extractInsuranceValuesByOrder($order)
    {
        $allItems    = $order->getAllItems();
        $description = '';
        $value       = 0;
        if (count($allItems)) {
            foreach ($allItems as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                $description .= ' | '.$item->getName();

                $value += $item->getRowTotalInclTax();
            }
        }
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return array(
            'goodsValue' => $value,
            'currency'   => $currencyCode,
            'content'    => $description
        );


    }

    /**
     * @param        $message
     * @param string $function
     * @param string $class
     * @param string $line
     *
     * @return $this
     */
    public function log($message, $function = '', $class = '', $line = '')
    {
        $location = ($class ? "$class::" : "") . $function . ($line ? " on line $line" : "");
        $this->logger->log(\Monolog\Logger::NOTICE, $message.($location?" at $location":''));

        return $this;
    }

    /**
     *
     * @param string $field
     * @param mixed  $store
     *
     * @return mixed
     */
    public function getConfigData($field, $store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore();
        }

        $carrierCode = $this->getDpdCarrierCode();

        return $this->scopeConfig->getValue("carriers/$carrierCode/$field", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     *
     * @param mixed $store
     *
     * @return string
     */
    public function getCarrierName($store = null)
    {
        return $this->getConfigData("title", $store);
    }

    /**
     *
     * @param mixed $store
     *
     * @return boolean
     */
    public function moduleIsActive($store = null)
    {
        return $this->getConfigData("active", $store) ? true : false;
    }


    /**
     *
     * @param \Magento\Shipping\Model\Carrier\AbstractCarrier $carrier
     *
     * @return boolean
     */
    public function isCarrierDpd(\Magento\Shipping\Model\Carrier\AbstractCarrier $carrier)
    {
        return $carrier instanceof \Zitec\Dpd\Model\Shipping\Carrier\Dpd;
    }

    /**
     * test if a order was submited using dpd shipping carrier
     *
     * @param $order
     *
     * @return bool
     */
    public function isDpdCarrierByOrder($order)
    {
        if (!is_object($order)) {
            return false;
        }
        $carrier = $this->getShippingCarrier($order);

        if (empty($carrier)) {
            return false;
        }

        return $this->isCarrierDpd($carrier);
    }

    public function getShippingCarrier($order)
    {
        $carrierModel = $order->getData('shipping_carrier');
        if (is_null($carrierModel)) {
            $carrierModel = false;
            /**
             * $method - carrier_method
             */
            $method = $order->getShippingMethod(true);
            if ($method instanceof \Magento\Framework\DataObject) {
                $className = $this->getConfigData('model');
                if ($className) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $carrierModel = $objectManager->create($className);
                }
            }
            $order->setData('shipping_carrier', $carrierModel);
        }
        return $carrierModel;
    }

    /**
     * check if the postcode was marked as a valid postcode or not
     *
     * @param $order
     */
    public function isValidAutoPostcode($order)
    {
        if (!is_object($order)) {
            return false;
        }
        if (!$this->isEnabledPostcodeAutocompleteByOrder($order)) {
            //it should be valid
            return 1;
        }
        if (!$this->isDpdCarrierByOrder($order)) {
            //it should be valid
            return 1;
        }
        $_shippingAddress = $order->getShippingAddress();
        $isValid          = $_shippingAddress->getValidAutoPostcode();

        return $isValid;

    }


    public function isEnabledPostcodeAutocompleteByOrder($order)
    {
        if (!is_object($order)) {
            return false;
        }
        $_shippingAddress = $order->getShippingAddress();
        $address          = $_shippingAddress->getData();
        if (!empty($address['country_id'])) {
            $countryName        = $this->directoryCountryFactory->create()->loadByCode($address['country_id'])->getName();
            $address['country'] = $countryName;
        } else {
            return false;
        }

        return ($this->dpdPostcodeSearchHelper->isEnabledAutocompleteForPostcode($countryName));
    }

    /**
     * chceck the address length to be less then DPD API requirements
     *
     * @param $shippingAddress
     *
     * @return bool
     */
    public function checkAddressStreetLength($shippingAddress)
    {

        $shippingAddressStreetArray = $shippingAddress->getStreet();
        if (is_array($shippingAddressStreetArray)) {
            $shippingAddressStreet = '';
            foreach ($shippingAddressStreetArray as $street) {
                $shippingAddressStreet .= '' . $street;
            }
            $shippingAddressStreet = trim($shippingAddressStreet);
        } else {
            $shippingAddressStreet = $shippingAddressStreetArray;
        }

        return (strlen($shippingAddressStreet) <= \Zitec_Dpd_Api_Configs::SHIPMENT_LIST_RECEIVER_STREET_MAX_LENGTH);
    }


    /**
     *
     * @param string|\Magento\Sales\Model\Order\Shipment|\Magento\Sales\Model\Order $shippingMethod
     *
     * @return boolean
     */
    public function isShippingMethodDpd($shippingMethod)
    {
        if ($shippingMethod instanceof \Magento\Sales\Model\Order\Shipment) {
            $shippingMethod = $shippingMethod->getOrder()->getShippingMethod();
        } elseif ($shippingMethod instanceof \Magento\Sales\Model\Order) {
            $shippingMethod = $shippingMethod->getShippingMethod();
        }

        return is_string($shippingMethod) && (strpos($shippingMethod, $this->getDpdCarrierCode()) !== false);
    }

    /**
     *
     * @param string $message
     */
    public function addNotice($message)
    {
        $this->messageManager->addNoticeMessage($message);
    }

    /**
     *
     * @param string $message
     */
    public function addError($message)
    {
        $this->messageManager->addErrorMessage($message);
    }

    /**
     *
     * @param string $message
     */
    public function addSuccess($message)
    {
        $this->messageManager->addSuccessMessage($message);
    }

    /**
     *
     * @param boolean $success
     * @param string  $message
     *
     * @return \Zitec_Dpd_Helper_Data
     */
    public function addSuccessError($success, $message)
    {
        if ($success) {
            $this->addSuccess($message);
        } else {
            $this->addError($message);
        }

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isAdmin()
    {
        $areaCode  = $this->appState->getAreaCode();

        return $areaCode == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    /**
     *
     * @param string $shipmentId
     *
     * @return boolean
     * @deprecated
     */
    public function isManifestClosed($shipmentId)
    {
        $shipsCollection = $this->dpdMysql4DpdShipCollectionFactory->create();
        $ships = $shipsCollection->getByShipmentId($shipmentId);

        return $ships->getManifest() ? true : false;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order\Shipment\Track $track
     *
     * @return boolean
     */
    public function isDpdTrack(\Magento\Sales\Model\Order\Shipment\Track $track)
    {
        return strpos($track->getCarrierCode(), $this->getDpdCarrierCode()) !== false;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return boolean
     */
    public function isCancelledWithDpd(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        return $this->isShippingMethodDpd($shipment->getOrder()->getShippingMethod()) && !$shipment->getShippingLabel();
    }


    /**
     *
     * @param string $shippingMethod
     *
     * @return string|boolean
     */
    public function getDPDServiceCode($shippingMethod)
    {
        $parts = explode('_', $shippingMethod);
        if (count($parts) == 2) {
            return $parts[1];
        } else {
            return false;
        }
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return boolean
     */
    public function isOrderCashOnDelivery(\Magento\Sales\Model\Order $order)
    {
        return $order->getPayment()->getMethod() == $this->getDpdPaymentCode() ? true : false;
    }

    /**
     * return default surcharge in checkout if no table rates defined
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param                        $shippingAmount
     *
     * @return mixed
     */
    public function returnDefaultBaseCashOnDeliverySurcharge(\Magento\Quote\Model\Quote $quote)
    {
        $amountType = $this->scopeConfig->getValue('payment/zitec_dpd_cashondelivery/payment_amount_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $quote->getStoreId());
        $amount     = $this->scopeConfig->getValue('payment/zitec_dpd_cashondelivery/payment_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $quote->getStoreId());
        if ($amountType == \Zitec_Dpd_Api_Configs::PAYMENT_AMOUNT_TYPE_FIXED) {
            return $amount;
        } else {
            $address   = $quote->getShippingAddress();
            $taxConfig = $this->taxConfig;
            /* @var $taxConfig Mage_Tax_Model_Config */

            $amount = $amount / 100;

            return $amount * ($this->getBaseValueOfShippableGoods($quote));
        }

    }

    /**
     * here is calculated the price of the quote payment method: cash on delivery using DPD
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param string                 $surcharge
     *
     * @return float
     */
    public function calculateQuoteBaseCashOnDeliverySurcharge(\Magento\Quote\Model\Quote $quote, $surcharge)
    {
        $address   = $quote->getShippingAddress();
        $taxConfig = $this->taxConfig;
        /* @var $taxConfig Mage_Tax_Model_Config */

        if (!$surcharge || !is_array($surcharge) || !isset($surcharge['cashondelivery_surcharge'])) {
            return 0;
        }
        $baseCashondeliverySurchargePercent = $this->parsePercentageValueAsFraction($surcharge['cashondelivery_surcharge']);
        if ($baseCashondeliverySurchargePercent !== false) {

            $baseCashondeliverySurcharge = $baseCashondeliverySurchargePercent * ($this->getBaseValueOfShippableGoods($quote));
            if (isset($surcharge['cod_min_surcharge'])) {
                $baseCashondeliverySurcharge = max(array((float)$surcharge['cod_min_surcharge'], $baseCashondeliverySurcharge));
            }
        } else {
            $baseCashondeliverySurcharge = (float)$surcharge['cashondelivery_surcharge'];
        }

        return $baseCashondeliverySurcharge;
    }

    /**
     * Parse a string of the form nn.nn% and returns the percent as a fraction.
     * It returns false if the string does not have the correct form.
     *
     * @param type $value
     *
     * @return boolean
     */
    public function parsePercentageValueAsFraction($value)
    {
        if (!is_string($value)) {
            return false;
        }
        $value = trim($value);
        if (strlen($value) < 2 || substr($value, -1) != '%') {
            return false;
        }
        $percentage = $this->parseDecimalValue(substr($value, 0, strlen($value) - 1));
        if ($percentage === false) {
            return false;
        }

        return $percentage / 100;
    }


    /**
     * Parse and validate positive decimal value
     * Return false if value is not decimal or is not positive
     *
     * @param string $value
     *
     * @return bool|float
     */
    public function parseDecimalValue($value)
    {
        if (!is_numeric($value)) {
            return false;
        }
        $value = (float)sprintf('%.4F', $value);
        if ($value < 0.0000) {
            return false;
        }

        return $value;
    }


    /**
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return float
     */
    public function getBaseValueOfShippableGoods(\Magento\Quote\Model\Quote $quote)
    {
        $baseTotalPrice = 0.0;
        $taxConfig      = $this->taxConfig;
        /* @var $taxConfig Mage_Tax_Model_Config */
        if ($quote->getAllItems()) {
            foreach ($quote->getAllItems() as $item) {
                /* @var $item Mage_Sales_Model_Quote_Item */

                if ($item->getProduct()->isVirtual() || $item->getParentItemId()) {
                    continue;
                }

                $baseTotalPrice += $taxConfig->shippingPriceIncludesTax($quote->getStore()) ? $item->getBaseRowTotalInclTax() : $item->getBaseRowTotal();
            }
        }

        return $baseTotalPrice;
    }


    /**
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return string
     */
    public function getCodPaymentType(\Magento\Sales\Model\Order $order)
    {
        return $order->getPayment()->getMethodInstance()->getConfigData("cod_payment_type", $order->getStoreId());
    }


    /**
     *
     * @param int $manifestId
     *
     * @return string
     */
    public function getDownloadManifestUrl($manifestId)
    {
        $helper = $this->backendHelper;

        return $helper->getUrl("dpd/shipment/downloadmanifest", array("manifest_id" => $manifestId));
    }


    /**
     * @return string
     */
    public function getDpdCarrierCode()
    {
        return \Zitec\Dpd\Model\Shipping\Carrier\Dpd::CARRIER_CODE;
    }


    /**
     * @return string
     */
    public function getDpdPaymentCode()
    {
        return \Zitec\Dpd\Model\Payment\Cashondelivery::CODE;
    }


    public function getConfigCountry()
    {
        return $this->configCountry;
    }
}
