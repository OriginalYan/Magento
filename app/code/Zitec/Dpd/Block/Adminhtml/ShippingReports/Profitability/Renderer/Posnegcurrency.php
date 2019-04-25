<?php
/**
 * Zitec_Dpd – shipping carrier extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @copyright  Copyright (c) 2014 Zitec COM
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability\Renderer;

/**
 * For columns of type 'currency'. Shown in green if it is positive, and red if negative.
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Posnegcurrency extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $currencyHelper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Pricing\Helper\Data $currencyHelper
    ) {
        parent::__construct($context);
        $this->currencyHelper = $currencyHelper;
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $value          = $row->getData($this->getColumn()->getIndex());
        $formattedValue = $this->currencyHelper->currency($value, true, false);
        $html           = '<span style="color: ' . ($value >= 0 ? 'green' : 'red') . '">' . $formattedValue . '</span>';

        return $html;
    }

    public function renderExport(\Magento\Framework\DataObject $row)
    {
        $value          = $row->getData($this->getColumn()->getIndex());
        $formattedValue = $this->currencyHelper->currency($value, true, false);

        return $formattedValue;
    }
}

