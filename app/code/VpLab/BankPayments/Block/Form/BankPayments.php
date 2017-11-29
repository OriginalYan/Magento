<?php

namespace VpLab\BankPayments\Block\Form;

/**
 * Block for Bank Payments payment method form
 */
class BankPayments extends \Magento\OfflinePayments\Block\Form\AbstractInstruction
{
    /**
     * Cash on delivery template
     *
     * @var string
     */
    protected $_template = 'form/bankpayments.phtml';
}
