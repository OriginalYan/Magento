<?php

namespace VpLab\CashPayments\Observer;

use Magento\Framework\Event\ObserverInterface;
use VpLab\CashPayments\Model\CashPayments;

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
        if ($payment->getMethod() == CashPayments::PAYMENT_METHOD_CASHPAYMENTS_CODE) {
            $payment->setAdditionalInformation(
                'instructions',
                $payment->getMethodInstance()->getInstructions()
            );
        }
    }
}
