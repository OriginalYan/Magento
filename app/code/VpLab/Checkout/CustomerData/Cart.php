<?php

namespace VpLab\Checkout\CustomerData;

class Cart extends \Magento\Checkout\CustomerData\Cart
{
    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of Object Manager
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data'); // Instance of Pricing Helper
        $totals = $this->getQuote()->getTotals();
        return [
            'summary_count' => $this->getSummaryCount(),
            'subtotal' => isset($totals['subtotal'])
                ? $this->checkoutHelper->formatPrice($totals['subtotal']->getValue())
                : 0,
            'subtotal_value' => isset($totals['subtotal'])
                ? $priceHelper->currency($totals['subtotal']->getValue(), false, false)
                : 0,
            'possible_onepage_checkout' => $this->isPossibleOnepageCheckout(),
            'items' => $this->getRecentItems(),
            'extra_actions' => $this->layout->createBlock('Magento\Catalog\Block\ShortcutButtons')->toHtml(),
            'isGuestCheckoutAllowed' => $this->isGuestCheckoutAllowed(),
            'website_id' => $this->getQuote()->getStore()->getWebsiteId()
        ];
    }
}
