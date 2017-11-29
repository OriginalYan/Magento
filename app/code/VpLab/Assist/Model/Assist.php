<?php

namespace VpLab\Assist\Model;

class Assist extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'vplab_assist';

    protected $_code = self::CODE;
    protected $_isGateway = false;
    protected $_isOffline = false;
    protected $_canRefund = true;
    protected $_isInitializeNeeded = false;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['RUB'];
    protected $_formBlockType = 'VpLab\Assist\Block\Form\Checkout';
    protected $_infoBlockType = 'VpLab\Assist\Block\Info\Checkout';

    protected $orderSender;
    protected $httpClientFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \VpLab\Assist\Helper\Checkout $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger);

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote and ($quote->getBaseGrandTotal() < $this->_minAmount) or ($this->_maxAmount and $quote->getBaseGrandTotal() > $this->_maxAmount)) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * Create POST date for sending to KliknPay.com
     */
    public function buildCheckoutRequest($quote)
    {
        $billing_address = $quote->getBillingAddress();

        $params = [];
        $params['Merchant_ID'] = $this->getConfigData('merchant_id');
        $params['OrderNumber'] = 'M' . $quote->getId();
        $params['Language'] = 'RU';
        $params['OrderAmount'] = number_format(round($quote->getGrandTotal(), 2), 2, '.', '');
        $params['OrderCurrency'] = 'RUB';
        $params['OrderComment'] = 'VPLaboratory.ru. Order #' . $quote->getId();
        $params['Lastname'] = $billing_address->getLastName();
        $params['Firstname'] = $billing_address->getFirstName();
        $params['Email'] = $quote->getCustomerEmail();
        $params['URL_RETURN_OK'] = $this->getReturnUrl() . '?tnx=' . $quote->getId();
        $params['URL_RETURN_NO'] = $this->getCancelUrl() . '?tnx=' . $quote->getId();
        $params['Checkvalue'] = $this->getCheckValue($quote);

        return $params;
    }

    public function validateResponse($orderNumber, $total, $key)
    {
        // TODO pass
    }

    public function postProcessing(\Magento\Sales\Model\Order $order, \Magento\Framework\DataObject $payment, $response)
    {
        // TODO pass
    }

    public function getCgiUrl()
    {
        $url = $this->getConfigData('sandbox') ? $this->getConfigData('cgi_url_test') : $this->getConfigData('cgi_url');
        return $url;
    }

    public function getRedirectUrl()
    {
        $url = $this->helper->getUrl($this->getConfigData('redirect_url'));
        return $url;
    }

    public function getReturnUrl()
    {
        $url = $this->helper->getUrl($this->getConfigData('return_url'));
        return $url;
    }

    public function getCancelUrl()
    {
        $url = $this->helper->getUrl($this->getConfigData('cancel_url'));
        return $url;
    }

    public function getInline()
    {
        return false;
    }

    public function getOrderStatus()
    {
        $value = $this->getConfigData('order_status');
        return $value;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // TODO pass
    }

    protected function getCheckValue($quote)
    {
        $data = [
            $this->getConfigData('merchant_id'),
            $quote->getId(),
            number_format(round($quote->getGrandTotal(), 2), 2, '.', ''),
            'RUB',
        ];
        $checkvalue = strtoupper(md5(strtoupper(md5($this->getConfigData('secret_word')) . md5(join(';', $data)))));
        return $checkvalue;
    }
}
