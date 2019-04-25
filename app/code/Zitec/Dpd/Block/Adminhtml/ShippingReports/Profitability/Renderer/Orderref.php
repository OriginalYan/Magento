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
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Orderref extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    public function __construct(
        \Magento\Backend\Helper\Data $backendHelper
    ) {
        $this->backendHelper = $backendHelper;
    }
    public function render(\Magento\Framework\DataObject $row)
    {
        $link = $this->backendHelper->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getEntityId()));
        $html = '<a href="' . $link . '">' . $row->getIncrementId() . '</a>';

        return $html;
    }

    public function renderExport(\Magento\Framework\DataObject $row)
    {
        return $row->getIncrementId();
    }
}

