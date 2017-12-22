<?php

namespace VpLab\Checkout\Model;

class Quote extends \Magento\Quote\Model\Quote
{
    public function getGrandTotal()
    {
        return round(parent::getGrandTotal(), 0);
    }

    public function setGrandTotal($value)
    {
        return parent::setGrandTotal(round($value, 0));
    }

    public function getBaseGrandTotal()
    {
        return round(parent::getBaseGrandTotal(), 0);
    }

    public function setBaseGrandTotal($value)
    {
        return parent::setBaseGrandTotal(round($value, 0));
    }

    public function getSubtotal()
    {
        return round(parent::getSubtotal(), 0);
    }

    public function setSubtotal($value)
    {
        return parent::setSubtotal(round($value, 0));
    }

    public function getBaseSubtotal()
    {
        return round(parent::getBaseSubtotal(), 0);
    }

    public function setBaseSubtotal($value)
    {
        return parent::setBaseSubtotal(round($value, 0));
    }

    public function getSubtotalWithDiscount()
    {
        return round(parent::getSubtotalWithDiscount(), 0);
    }

    public function setSubtotalWithDiscount($value)
    {
        return parent::setSubtotalWithDiscount(round($value, 0));
    }

    public function getBaseSubtotalWithDiscount()
    {
        return round(parent::getBaseSubtotalWithDiscount(), 0);
    }

    public function setBaseSubtotalWithDiscount($value)
    {
        return parent::setBaseSubtotalWithDiscount(round($value, 0));
    }

    public function beforeSave()
    {
        parent::beforeSave();

        $this->setGrandTotal(round($this->getGrandTotal(), 0));
        $this->setBaseGrandTotal(round($this->getBaseGrandTotal(), 0));
        $this->setSubtotal(round($this->getSubtotal(), 0));
        $this->setBaseSubtotal(round($this->getBaseSubtotal(), 0));
        $this->setSubtotalWithDiscount(round($this->getSubtotalWithDiscount(), 0));
        $this->setBaseSubtotalWithDiscount(round($this->getBaseSubtotalWithDiscount(), 0));
    }
}
