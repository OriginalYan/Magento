<?php

namespace VpLab\Checkout\Model;

class Order extends \Magento\Sales\Model\Order
{
    public function formatPrice($price, $addBrackets = false)
    {
        return $this->formatPricePrecision($price, 0, $addBrackets);
    }

    public function formatBasePrice($price)
    {
        return $this->formatBasePricePrecision($price, 0);
    }

    public function setBaseDiscountAmount($amount)
    {
        return parent::setBaseDiscountAmount(round($amount, 0, PHP_ROUND_HALF_DOWN));
    }

    public function setBaseDiscountInvoiced($baseDiscountInvoiced)
    {
        return parent::setBaseDiscountInvoiced(round($baseDiscountInvoiced, 0, PHP_ROUND_HALF_DOWN));
    }

    public function setBaseGrandTotal($amount)
    {
        return parent::setBaseGrandTotal(round($amount, 0));
    }

    public function setBaseSubtotal($amount)
    {
        return parent::setBaseSubtotal(round($amount, 0));
    }

    public function setBaseTaxAmount($amount)
    {
        return parent::setBaseTaxAmount(round($amount, 0));
    }

    public function setBaseTotalPaid($baseTotalPaid)
    {
        return parent::setBaseTotalPaid(round($baseTotalPaid, 0));
    }

    public function setBaseTotalInvoiced($baseTotalInvoiced)
    {
        return parent::setBaseTotalInvoiced(round($baseTotalInvoiced, 0));
    }

    public function setBaseTotalDue($baseTotalDue)
    {
        return parent::setBaseTotalDue(round($baseTotalDue, 0));
    }

    public function setDiscountAmount($amount)
    {
        return parent::setDiscountAmount(round($amount, 0, PHP_ROUND_HALF_DOWN));
    }

    public function setDiscountInvoiced($discountInvoiced)
    {
        return parent::setDiscountInvoiced(round($discountInvoiced, 0, PHP_ROUND_HALF_DOWN));
    }

    public function setGrandTotal($amount)
    {
        return parent::setGrandTotal(round($amount, 0));
    }

    public function setSubtotal($amount)
    {
        return parent::setSubtotal(round($amount, 0));
    }

    public function setTaxAmount($amount)
    {
        return parent::setTaxAmount(round($amount, 0));
    }

    public function setTotalPaid($totalPaid)
    {
        return parent::setTotalPaid(round($totalPaid, 0));
    }

    public function setTotalInvoiced($totalInvoiced)
    {
        return parent::setTotalInvoiced(round($totalInvoiced, 0));
    }

    public function setTotalDue($totalDue)
    {
        return parent::setTotalDue(round($totalDue, 0));
    }

    public function setSubtotalInclTax($amount)
    {
        return parent::setSubtotalInclTax(round($amount, 0));
    }
}
