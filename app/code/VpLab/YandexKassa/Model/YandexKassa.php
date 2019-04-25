<?php

namespace VpLab\YandexKassa\Model;

class YandexKassa extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'vplab_yandex';

    protected $_code = self::CODE;
    protected $_isGateway = false;
    protected $_isOffline = false;
    protected $_canRefund = true;
    protected $_isInitializeNeeded = false;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['RUB', 'RUR'];
    protected $_formBlockType = 'VpLab\YandexKassa\Block\Form\Checkout';
    protected $_infoBlockType = 'VpLab\YandexKassa\Block\Info\Checkout';
    protected $_precision = 0;

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
        \VpLab\YandexKassa\Helper\Checkout $helper,
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

        $order_id = $quote->getReservedOrderId();
        if (!$order_id) {
            $order_id = $quote->getOrigOrderId();
        }
        if (!$order_id) {
            $order_id = $quote->getId();
        }

        $params = [];
        $params['shopId'] = $this->getConfigData('shop_id');
        $params['scid'] = $this->getConfigData('scid');
        $params['sum'] = number_format(round($quote->getGrandTotal(), $this->_precision), 2, '.', '');
        $params['customerNumber'] = trim($billing_address->getFirstName() . ' ' . $billing_address->getLastName());
        $params['paymentType'] = '';
        $params['orderNumber'] = $order_id;
        $params['shopSuccessURL'] = $this->getSuccessUrl();
        $params['shopFailURL'] = $this->getCancelUrl();
        $params['cps_email'] = $quote->getCustomerEmail();
        $params['cps_phone'] = $billing_address->getTelephone();
        $params['tnx_id'] = $quote->getId();
        $params['ym_merchant_receipt'] = $this->getMerchantReceiptData($quote, $billing_address);

        return $params;
    }

    public function validateResponse($params)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get('\Psr\Log\LoggerInterface');

        $data = [
            $params['action'],
            isset($params['orderSumAmount']) ? $params['orderSumAmount'] : '',
            isset($params['orderSumCurrencyPaycash']) ? $params['orderSumCurrencyPaycash'] : '',
            isset($params['orderSumBankPaycash']) ? $params['orderSumBankPaycash'] : '',
            isset($params['shopId']) ? $params['shopId'] : '',
            isset($params['invoiceId']) ? $params['invoiceId'] : '',
            isset($params['customerNumber']) ? $params['customerNumber'] : '',
            $this->getConfigData('shop_password'),
        ];
        $str = join(';', $data);
        $logger->addDebug("[YANDEX] String to md5: " . $str);
        $md5 = strtoupper(md5($str));
        if ($md5 != strtoupper($params['md5'])) {
            $logger->addDebug("[YANDEX] Expected md5:" . $md5 . ", recieved md5: " . $params['md5']);
            return false;
        }
        return true;
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

    public function getSuccessUrl()
    {
        $url = $this->helper->getUrl($this->getConfigData('success_url'));
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

    protected function getMerchantReceiptData($quote, $billing_address)
    {
        $result = [];
        if (strpos($billing_address->getEmail(), '@') > 0) {
            $result['customerContact'] = trim($billing_address->getEmail());
        } elseif (trim($billing_address->getTelephone()) != '') {
            $phone = trim($billing_address->getTelephone());
            if ($phone[0] != '+') {
                $phone = '+' . $phone;
            }
            $result['customerContact'] = $phone;
        } else {
            $result['customerContact'] = '';
        }

        if ($quote->getSubtotal() > $quote->getSubtotalWithDiscount()) {
            $discount = ($quote->getSubtotal() - $quote->getSubtotalWithDiscount()) / $quote->getSubtotal();
        } else {
            $discount = 0;
        }

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
                $qty = $p->getQty();
            } else {
                $price = $item->getPrice();
                $qty = $item->getQty();
            }
            $name = str_replace(['%', ':', '|'], ' ', $item->getProduct()->getName());
            if ($discount > 0) {
                $price = round($price * (1 - $discount), 2);
                $name .= ' (Скидка ' . round($discount * 100) . '%)';
            }

            $value = [
                'quantity' => $qty,
                'price' => [
                    'amount' => number_format(round($price, 2), 2, '.', ''),
                ],
                'tax' => 4,
                'text' => $name,
                'paymentMethodType' => 'full_prepayment',
                'paymentSubjectType' => 'commodity',
            ];
            $basket[] = $value;
        }

        $shipping_address = $quote->getShippingAddress();
        $shipping_amount = $shipping_address->getShippingAmount();

        if ($shipping_amount > 0) {
            $shipping_method = $quote->getShippingAddress()->getShippingDescription();
            $basket[] = [
                'quantity' => 1,
                'price' => [
                    'amount' => number_format(round($shipping_amount, 2), 2, '.', ''),
                ],
                'tax' => 4,
                'text' => $shipping_method,
                'paymentMethodType' => 'full_prepayment',
                'paymentSubjectType' => 'service',
            ];
        }

        $result['items'] = $this->fixAmountsAfterDiscount($quote, $basket);

        return json_encode($result);
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // TODO pass
    }

    protected function fixAmountsAfterDiscount($quote, $basket)
    {
        $totalAmount = round($quote->getGrandTotal(), $this->_precision);

        $priceSum = 0;
        foreach ($basket as $item) {
            $priceSum += $item['quantity'] * $item['price']['amount'];
        }
        if ($priceSum == $totalAmount) {
            return $basket;
        }
        $delta = $totalAmount - $priceSum;
        $is_fixed = false;
        foreach ($basket as $k => $v) {
            if ($v['quantity'] != 1 or $v['price']['amount'] <= 0) {
                continue;
            }
            $basket[$k]['price']['amount'] = number_format($basket[$k]['price']['amount'] + $delta, 2, '.', '');
            $is_fixed = true;
            break;
        }
        if (!$is_fixed) {
            foreach ($basket as $k => $v) {
                if (($delta * 100) % $v['quantity'] > 0 or $v['price']['amount'] <= 0) {
                    continue;
                }
                $basket[$k]['price']['amount'] = number_format($basket[$k]['price']['amount'] + $delta / $v['quantity'], 2, '.', '');
                $is_fixed = true;
                break;
            }
        }
        if (!$is_fixed) {
            $basket[0]['price']['amount'] = number_format($basket[0]['price']['amount'] + $delta / $basket[0]['quantity'], 2, '.', '');
        }
        return $basket;
    }
}
