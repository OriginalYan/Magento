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

namespace Zitec\Dpd\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Pickup implements ObserverInterface
{

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    public function __construct(
        \Magento\Backend\Helper\Data $backendHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->backendHelper = $backendHelper;
        $this->dpdHelper = $dpdHelper;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return;
        if (!$this->dpdHelper->moduleIsActive()) {
            return;
        }
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction
            && (($block->getRequest()->getControllerName() == 'sales_shipment'))
        ) {
            $block->addItem('create_dpd_pickup', array(
                'label'      => __('Arrange DPD Pickup'),
                'url'        => $this->backendHelper->getUrl('dpd/shipment/createpickup'),
                'additional' => array(
                    'zitec_dpd_pickup_date'        => array(
                        'name'  => 'zitec_dpd_pickup_date',
                        'type'  => 'text',
                        'class' => 'required-entry',
                        'label' => __('Date (DD/MM/YYYY)')
                    ),
                    'zitec_dpd_pickup_from'        => array(
                        'name'  => 'zitec_dpd_pickup_from',
                        'type'  => 'time',
                        'class' => 'required-entry',
                        'label' => __('Between')
                    ),
                    'zitec_dpd_pickup_to'          => array(
                        'name'  => 'zitec_dpd_pickup_to',
                        'type'  => 'time',
                        'class' => 'required-entry',
                        'label' => __('and')
                    ),
                    'zitec_dpd_pickup_instruction' => array(
                        'name'  => 'zitec_dpd_pickup_instruction',
                        'type'  => 'text',
                        'label' => __('Instructions')
                    ),
                )
            ));
        }
    }
}


