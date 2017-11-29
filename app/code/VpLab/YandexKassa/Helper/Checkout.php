<?php

namespace VpLab\YandexKassa\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;

class Checkout extends AbstractHelper
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_session;
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $_resultFactory;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    ) {
        $this->_session = $session;
        $this->_quote = $quote;
        $this->_quoteManagement = $quoteManagement;
        $this->_resultFactory = $resultFactory;

        parent::__construct($context);
    }

    public function cancelCurrentOrder($comment)
    {
        $order = $this->_session->getLastRealOrder();
        if ($order->getId() and $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    public function restoreQuote()
    {
        $this->_session->restoreQuote();
    }

    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }

    public function makeResponse($action, $invoice_id, $code, $msg = null)
    {
        $dt = date('c');
        $content = '<' . $action . 'Response performedDatetime="' . $dt . '" code="' . $code . '" invoiceId="' . $invoice_id . '" shopId="' . $this->getShopId() . '"' . ($msg != null ? 'message="' . $msg . '"' : "") . '/>';

        $resp = $this->_resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        $resp->setHeader('Content-Type', 'text/xml');
        $resp->setContents($content);
        return $resp;
    }

    public function getShopId()
    {
        return $this->scopeConfig->getValue('payment/vplab_yandex/shop_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
