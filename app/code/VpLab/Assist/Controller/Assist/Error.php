<?php

namespace VpLab\Assist\Controller\Assist;

class Error extends \VpLab\Assist\Controller\Checkout
{
    public function execute()
    {
        $this->_logger->addDebug('ASSIST ERROR: execute()');
        $this->_logger->addDebug("GET: " . print_r($_GET, true));

        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl('checkout'));
    }
}
