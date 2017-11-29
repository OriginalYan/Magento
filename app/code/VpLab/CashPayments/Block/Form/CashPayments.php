<?php

namespace VpLab\CashPayments\Block\Form;

/**
 * Block for Cash On Delivery payment method form
 */
class CashPayments extends \Magento\OfflinePayments\Block\Form\AbstractInstruction
{
    /**
     * Cash on delivery template
     *
     * @var string
     */
    protected $_template = 'form/cashpayments.phtml';
}
