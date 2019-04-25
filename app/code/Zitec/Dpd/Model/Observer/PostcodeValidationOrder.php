<?php

namespace Zitec\Dpd\Model\Observer;


class PostcodeValidationOrder
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;
    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;

    public function __construct(
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Magento\Framework\View\LayoutInterface $layout
    )
    {
        $this->layout = $layout;
        $this->dpdHelper = $dpdHelper;
    }

    public function afterToHtml(
        \Magento\Sales\Block\Adminhtml\Order\View\Info\Interceptor $original,
        $html
    ) {
        $order = $original->getOrder();

        $isDpdCarrier             = $this->dpdHelper->isDpdCarrierByOrder($order);
        $isPCAutocompleterEnabled = $this->dpdHelper->isEnabledPostcodeAutocompleteByOrder($order);
        if ($isDpdCarrier) {

            if ($isPCAutocompleterEnabled) {

                $block = $this->layout->createBlock('Magento\Framework\View\Element\Template');
                $block->setOrder($order);
                $block->setTemplate('Zitec_Dpd::sales/order/address/postcode/alert-problem.phtml');
                $postcodeAlertHtml = $block->toHtml();

                $html .= $postcodeAlertHtml;
            }

            $block = $this->layout->createBlock('Magento\Framework\View\Element\Template');
            $block->setOrder($order);
            $block->setTemplate('Zitec_Dpd::sales/order/address/street/alert-problem.phtml');
            $streetAlertHtml = $block->toHtml();

            $html .= $streetAlertHtml;

            return $html;
        }

        //do something with $html
        //make sure you return a value of the same type as the original method
        return $html;

    }
}
