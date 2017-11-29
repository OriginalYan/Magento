<?php

namespace VpLab\Assist\Controller\Assist;

class Redirect extends \VpLab\Assist\Controller\Checkout
{
    public function execute()
    {
        $this->_logger->addDebug('VpLab\Assist\Controller\Assist\Redirect::execute()');

        if (!$this->getRequest()->isAjax()) {
            $this->_cancelPayment();
            $this->_checkoutSession->restoreQuote();
            $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl('checkout'));
        }

        $quote = $this->getQuote();
        $email = $this->getRequest()->getParam('email');
        $this->getCustomerSession()->setData('email', $email);
        if ($this->getCustomerSession()->isLoggedIn()) {
            $this->getCheckoutSession()->loadCustomerQuote();
            $quote->updateCustomerData($this->getQuote()->getCustomer());
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
        } else {
            $quote->setCustomerEmail($email);
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
        }
        $quote->reserveOrderId();
        $this->quoteRepository->save($quote);

        $params = [];
        $params['fields'] = $this->getPaymentMethod()->buildCheckoutRequest($quote);
        $params['url'] = $this->getPaymentMethod()->getCgiUrl();
        $params['inline'] = $this->getPaymentMethod()->getInline();

        $quote->getItems();

        $this->_logger->addDebug(print_r($params, true));

        return $this->resultJsonFactory->create()->setData($params);
    }
}
