<?php

namespace Zitec\Dpd\Model\Observer;


class PostcodeValidationAddress
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    )
    {
        $this->layout = $layout;
    }

    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\Address\Interceptor $original,
        $html
    ) {
        $block = $this->layout->createBlock('Magento\Framework\View\Element\Template');
        $block->setTemplate('Zitec_Dpd::sales/order/address/postcode/validate.phtml');
        $postcodeValidation = $block->toHtml();

        $html .= $postcodeValidation;

        return $html;
    }
}
