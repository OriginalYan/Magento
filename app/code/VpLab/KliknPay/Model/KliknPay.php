<?php

namespace VpLab\KliknPay\Model;

class KliknPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'vplab_kliknpay';

    protected $_code = self::CODE;
    protected $_isGateway = false;
    protected $_isOffline = false;
    protected $_canRefund = true;
    protected $_isInitializeNeeded = false;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['EUR'];
    protected $_formBlockType = 'VpLab\KliknPay\Block\Form\Checkout';
    protected $_infoBlockType = 'VpLab\KliknPay\Block\Info\Checkout';

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
        \VpLab\KliknPay\Helper\Checkout $helper,
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
        $params['ID'] = $this->getConfigData('merchant_id');
        $params['NOM'] = $billing_address->getLastName();
        $params['PRENOM'] = $billing_address->getFirstName();
        $params['ADRESSE'] = $billing_address->getStreet()[0];
        $params['CODEPOSTAL'] = $billing_address->getPostcode();
        $params['VILLE'] = $billing_address->getCity();
        $params['PAYS'] = $billing_address->getCountryId();
        $params['REGION'] = $billing_address->getRegion();
        $params['EMAIL'] = $quote->getCustomerEmail();
        $params['TEL'] = $billing_address->getTelephone();
        $params['MONTANT'] = number_format(round($quote->getGrandTotal(), 2), 2, '.', '');
        $params['RETOUR'] = $quote->getId();
        $params['RETOURVOK'] = $quote->getId();
        $params['RETOURVHS'] = $quote->getId();
        $params['L'] = 'en';

        $data = [];
        $parents = [];
        foreach ($quote->getItemsCollection() as $item) {
            $data[$item->getId()] = $item;
            $parents[] = $item->getParentItemId();
        }
        $basket = [];
        foreach ($data as $k => $item) {
            if (in_array($item->getId(), $parents)) {
                continue;
            }
            if ($item->getParentItemId() and isset($data[$item->getParentItemId()])) {
                $p = $data[$item->getParentItemId()];
                $price = $p->getPrice();
            } else {
                $price = $item->getPrice();
            }
            $name = str_replace(['%', ':', '|'], ' ', $item->getProduct()->getName());
            $txt = 'REF:' . $item->getProduct()->getId() . '%Q:' . $item->getQty() . '%PRIX:' . number_format(round($price, 2), 2, '.', '') . '%PROD:' . $name . '|';
            $basket[] = $txt;
        }
        $params['DETAIL'] = join('', $basket);

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
}
