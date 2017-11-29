<?php

namespace VpLab\YandexKassa\Controller\Yandex;

class Error extends \VpLab\YandexKassa\Controller\Checkout
{
    public function execute()
    {
        $this->_logger->addDebug('VpLab\YandexKassa\Controller\Yandex\Error::execute()');
        $this->_logger->addDebug("GET: " . print_r($_GET, true));
        $this->_logger->addDebug("POST: " . print_r($_POST, true));

        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl('checkout'));
    }
}
