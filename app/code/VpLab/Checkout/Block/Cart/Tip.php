<?php

namespace VpLab\Checkout\Block\Cart;

class Tip extends \Magento\Checkout\Block\Cart\AbstractCart
{
    public static $FREE_DELIVERY_MIN_AMOUNT = 3000;

    public function getSubTotal()
    {
        $quote = $this->getQuote();
        return $quote->getSubtotal();
    }
}
