<?php

namespace VpLab\KliknPay\Controller\KliknPay;

class Error extends \VpLab\KliknPay\Controller\Checkout
{
    public function execute()
    {
        $this->_logger->addDebug('KLIKNPAY ERROR: execute()');
        $this->_logger->addDebug("GET: " . print_r($_GET, true));

        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl('checkout'));
    }
}
