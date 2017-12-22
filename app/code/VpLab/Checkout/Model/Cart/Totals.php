<?php

namespace VpLab\Checkout\Model\Cart;

class Totals extends \Magento\Quote\Model\Cart\Totals
{
    public function setGrandTotal($grandTotal)
    {
        $grandTotal = round($grandTotal, 0);
        return parent::setGrandTotal($grandTotal);
    }

    public function setBaseGrandTotal($baseGrandTotal)
    {
        $baseGrandTotal = round($baseGrandTotal, 0);
        return parent::setBaseGrandTotal($baseGrandTotal);
    }

    public function setSubtotal($subtotal)
    {
        $subtotal = round($subtotal, 0);
        return parent::setSubtotal($subtotal);
    }

    public function setBaseSubtotal($baseSubtotal)
    {
        $baseSubtotal = round($baseSubtotal, 0);
        return parent::setBaseSubtotal($baseSubtotal);
    }

    public function getDiscountAmount()
    {
        $value = parent::getDiscountAmount();
        if ($value !== null) {
            $value = round($value, 0, PHP_ROUND_HALF_DOWN);
        }
        return $value;
    }

    public function setDiscountAmount($discountAmount)
    {
        if ($discountAmount !== null) {
            $discountAmount = round($discountAmount, 0, PHP_ROUND_HALF_DOWN);
        }
        return parent::setDiscountAmount($discountAmount);
    }

    public function getBaseDiscountAmount()
    {
        $value = parent::getBaseDiscountAmount();
        if ($value !== null) {
            $value = round($value, 0, PHP_ROUND_HALF_DOWN);
        }
        return $value;
    }

    public function setBaseDiscountAmount($baseDiscountAmount)
    {
        if ($baseDiscountAmount !== null) {
            $baseDiscountAmount = round($baseDiscountAmount, 0, PHP_ROUND_HALF_DOWN);
        }
        return parent::setBaseDiscountAmount($baseDiscountAmount);
    }

    public function setSubtotalWithDiscount($subtotalWithDiscount)
    {
        $subtotalWithDiscount = round($subtotalWithDiscount, 0);
        return parent::setSubtotalWithDiscount($subtotalWithDiscount);
    }

    public function setBaseSubtotalWithDiscount($baseSubtotalWithDiscount)
    {
        $baseSubtotalWithDiscount = round($baseSubtotalWithDiscount, 0);
        return parent::setBaseSubtotalWithDiscount($baseSubtotalWithDiscount);
    }

    public function setSubtotalInclTax($subtotalInclTax)
    {
        $subtotalInclTax = round($subtotalInclTax, 0);
        return parent::setSubtotalInclTax($subtotalInclTax);
    }

    public function setBaseSubtotalInclTax($baseSubtotalInclTax)
    {
        $baseSubtotalInclTax = round($baseSubtotalInclTax, 0);
        return parent::setBaseSubtotalInclTax($baseSubtotalInclTax);
    }
}
