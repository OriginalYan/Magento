<?php

namespace VpLab\CashPayments\Model;

/**
 * Cash on delivery payment method model
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class CashPayments extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CASHPAYMENTS_CODE = 'cashpayments';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CASHPAYMENTS_CODE;

    /**
     * Cash On Delivery payment block paths
     *
     * @var string
     */
    protected $_formBlockType = 'VpLab\CashPayments\Block\Form\CashPayments';

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    protected $_minAmount = null;
    protected $_maxAmount = null;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger);

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote and ($quote->getBaseGrandTotal() < $this->_minAmount) or ($this->_maxAmount and $quote->getBaseGrandTotal() > $this->_maxAmount)) {
            return false;
        }

        if (method_exists($quote, 'getShippingAddress')) {
            $address = $quote->getShippingAddress();
            $city = $address->getCity();
            if (!$city) {
                return false;
            }
            $city = trim(mb_strtolower($city));
            if ($city != 'москва' and $city != 'moscow') {
                return false;
            }
        }
        return parent::isAvailable($quote);
    }
}
