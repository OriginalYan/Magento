<?php

namespace VpLab\BankPayments\Observer;

use Magento\Framework\Event\ObserverInterface;
use VpLab\BankPayments\Model\BankPayments;

class BeforeOrderPaymentSaveObserver implements ObserverInterface
{
    /**
     * Sets current instructions for bank transfer account
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        if ($payment->getMethod() == BankPayments::PAYMENT_METHOD_BANKPAYMENTS_CODE) {
            $payment->setAdditionalInformation(
                'instructions',
                $payment->getMethodInstance()->getInstructions()
            );
        }
    }
}
