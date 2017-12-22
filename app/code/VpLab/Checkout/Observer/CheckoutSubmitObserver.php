<?php

namespace VpLab\Checkout\Observer;

class CheckoutSubmitObserver implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getData('quote');

        $quote->setGrandTotal(round($quote->getGrandTotal(), 0));
        $quote->setBaseGrandTotal(round($quote->getBaseGrandTotal(), 0));
        $quote->setSubtotal(round($quote->getSubtotal(), 0));
        $quote->setBaseSubtotal(round($quote->getBaseSubtotal(), 0));
        $quote->setSubtotalWithDiscount(round($quote->getSubtotalWithDiscount(), 0));
        $quote->setBaseSubtotalWithDiscount(round($quote->getBaseSubtotalWithDiscount(), 0));

        return $this;
    }
}
