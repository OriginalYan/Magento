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

namespace Zitec\Dpd\Model\Payment;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Cashondelivery extends \Magento\Payment\Model\Method\AbstractMethod
{

    const CODE = 'zitec_dpd_cashondelivery';

    protected $_code = self::CODE;

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_surcharge = null;
    protected $_order = null;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Zitec\Dpd\Helper\Payment
     */
    protected $dpdPaymentHelper;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Carrier\TablerateFactory
     */
    protected $dpdMysql4CarrierTablerateFactory;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /** @var \Zitec\Dpd\Model\Payment\Cashondelivery\Source\Country */
    protected $codCountry;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Cashondelivery constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Zitec\Dpd\Helper\Payment $dpdPaymentHelper
     * @param \Zitec\Dpd\Helper\Data $dpdHelper
     * @param \Zitec\Dpd\Model\Mysql4\Carrier\TablerateFactory $dpdMysql4CarrierTablerateFactory
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Zitec\Dpd\Model\Payment\Cashondelivery\Source\Country $codCountry
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Tax\Model\Config $taxConfig,
        \Zitec\Dpd\Helper\Payment $dpdPaymentHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Model\Mysql4\Carrier\TablerateFactory $dpdMysql4CarrierTablerateFactory,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Zitec\Dpd\Model\Payment\Cashondelivery\Source\Country $codCountry,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->checkoutSession = $checkoutSession;
        $this->taxConfig = $taxConfig;
        $this->eventManager = $context->getEventDispatcher();
        $this->dpdPaymentHelper = $dpdPaymentHelper;
        $this->dpdHelper = $dpdHelper;
        $this->dpdMysql4CarrierTablerateFactory = $dpdMysql4CarrierTablerateFactory;
        $this->taxHelper = $taxHelper;
        $this->registry = $registry;
        $this->request = $request;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->codCountry = $codCountry;
        $this->priceCurrency = $priceCurrency;
    }
    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        $order = $this->fetchOrder();
        $title = '';
        $store = null;
        $quote = $this->checkoutSession->getQuote();
        if ($order) {
            $title = $this->getConfigData('title', $order->getStoreId());
            $store = $order->getStoreId();
        } else {
            if ($quote) {
                $title = $this->getConfigData('title', $quote->getStoreId());
                $store = $quote->getStoreId();
            }
        }
        $surcharge = $this->getSurcharge($quote);
        if ($surcharge > 0) {
            $title     = $title . ' (+ ' . $this->priceCurrency->format($surcharge, false);
            $taxConfig = $this->taxConfig;
            if ($taxConfig->shippingPriceIncludesTax($store)) {
                $title .= __(' incl. tax');
            } else {
                $title .= __(' excl. tax');
            }
            $title .= ') ';
        }

        return $title;
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $checkResult       = new \StdClass;
        $isActive          = (bool)(int)$this->getConfigData('active', $quote ? $quote->getStoreId() : null);
        $shippingMethodRaw = $quote->getShippingAddress()->getShippingMethod();
        $product           = $this->dpdHelper->getDPDServiceCode($shippingMethodRaw);

        if (!$isActive ||
            !$quote ||
            !$this->dpdHelper->isShippingMethodDpd($shippingMethodRaw) ||
            !in_array($product, explode(',', $this->getConfigData('specificproducto', $quote->getStoreId()))) ||
            is_null($this->getSurcharge($quote))
        ) {
            $isActive = false;
        }

        $checkResult->isAvailable      = $isActive;
        $checkResult->isDeniedInConfig = !$isActive; // for future use in observers
        $this->eventManager->dispatch('payment_method_is_active', array(
            'result'          => $checkResult,
            'method_instance' => $this,
            'quote'           => $quote,
        ));

        //MAGENTO 2 does not support recurring profiles
        // disable method if it cannot implement recurring profiles management and there are recurring items in quote
        /*if ($checkResult->isAvailable) {
            $implementsRecurring = $this->canManageRecurringProfiles();
            // the $quote->hasRecurringItems() causes big performance impact, thus it has to be called last
            if ($quote && !$implementsRecurring && $quote->hasRecurringItems()) {
                $checkResult->isAvailable = false;
            }
        }*/

        return $checkResult->isAvailable;
    }

    private function getSurcharge(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        if ($this->_surcharge === null) {
            $order = $this->fetchOrder();
            if ($order) {
                $this->_surcharge = $order->getData('base_zitec_dpd_cashondelivery_surcharge');
            } else {
                if ($quote) {
                    $shippingAddress = $quote->getShippingAddress();

                    $request = $this->dataObjectFactory->create();

                    $request->setWebsiteId($this->dpdPaymentHelper->getWebsiteId());
                    $request->setDestCountryId($shippingAddress->getCountryId());
                    $request->setDestRegionId($shippingAddress->getRegionId());
                    $request->setDestPostcode($shippingAddress->getPostcode());
                    $request->setPackageWeight($shippingAddress->getWeight());
                    if ($this->_getTaxHelper()->shippingPriceIncludesTax($quote->getStoreId())) {
                        $request->setData('zitec_table_price', $shippingAddress->getBaseSubtotalInclTax());
                    } else {
                        $request->setData('zitec_table_price', $shippingAddress->getBaseSubtotal());
                    }
                    $request->setMethod(str_replace($this->dpdHelper->getDPDCarrierCode() . '_', '', $shippingAddress->getShippingMethod()));
                    $tablerateSurcharge = $this->dpdMysql4CarrierTablerateFactory->create()->getCashOnDeliverySurcharge($request);

                    if (is_null($tablerateSurcharge) || (is_array($tablerateSurcharge)&& is_null($tablerateSurcharge['cashondelivery_surcharge']))) {
                        return null;
                    } elseif (!empty($tablerateSurcharge)) {
                        $this->_surcharge = $this->dpdHelper->calculateQuoteBaseCashOnDeliverySurcharge($quote, $tablerateSurcharge);
                    } else {
                        $this->_surcharge = $this->dpdHelper->returnDefaultBaseCashOnDeliverySurcharge($quote);
                    }
                }
            }
        }

        return $this->_surcharge;
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
     * @return \Magento\Framework\Model\AbstractModel|mixed|null
     */
    public function fetchOrder()
    {
        if (is_null($this->_order)) {
            if ($this->dpdHelper->isAdmin()) {
                $this->_order = $this->registry->registry('current_order');
                if (!$this->_order && $this->request->getParam('order_id')) {
                    $this->_order = $this->salesOrderFactory->create()->load($this->request->getParam('order_id'));
                }
            } else {
                $order_id = $this->request->getParam('order_id');
                if ($order_id) {
                    $this->_order = $this->salesOrderFactory->create()->load($this->request->getParam('order_id'));
                }
            }
        }

        return $this->_order;
    }

    /**
     * To check billing country is allowed for the payment method
     *
     * @return bool
     */
    public function canUseForCountry($country)
    {
        $allowedCountries = $this->codCountry->getAllAllowedCountries();
        $canUseForCountry = parent::canUseForCountry($country) && (!$allowedCountries || in_array($country, $allowedCountries));

        return $canUseForCountry ? true : false;
    }
}

