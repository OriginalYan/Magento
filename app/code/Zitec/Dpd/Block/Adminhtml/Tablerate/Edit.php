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

namespace Zitec\Dpd\Block\Adminhtml\Tablerate;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Zitec\Dpd\Helper\Tablerate\Directory;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Edit extends Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Directory
     */
    protected $tableRatesDirectoryHelper;

    /**
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Zitec\Dpd\Helper\Tablerate\Directory $tableRatesDirectoryHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Directory $tableRatesDirectoryHelper,
        array $data = []
    ) {

        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->tableRatesDirectoryHelper = $tableRatesDirectoryHelper;

        $this->_objectId   = 'tablerate_id';
        $this->_blockGroup = 'zitec_dpd';
        $this->_controller = 'adminhtml_tablerate';
        $model             = $this->registry->registry('tablerate_data');
        /* @var $model \Zitec\Dpd\Model\Tablerate\Tablerate */

        $this->updateButton('save', 'label', __('Save'));
        if ($model->getId()) {

            $this->addButton('duplicate', [
                'label' => __('Duplicate'),
                'class' => 'add',
            ]);

            $this->addButton('delete', [
                'label'=> __('Delete'),
                'class' => 'delete',
            ]);
        } else {
            $this->removeButton('delete');
        }

        $json = $this->tableRatesDirectoryHelper->getRegionJson2();

        //TODO: move js outside this file
        $this->_formScripts[] = "
            require(['jquery', 'zitecDpdEditTablerate', 'mage/adminhtml/form'], function($, editTableRate){
                editTableRate.init($);
                var updater = new RegionUpdater('{$model->getMappedName('dest_country_id')}', 'none', '{$model->getMappedName('dest_region_id')}', $json, 'disable');
            });
        ";

        //TODO: replace this with the correct way of doing JS translations in Magento 2
        $this->_formScripts[] = "

    var WEIGHT_AND_ABOVE_LABEL = '" . __("Weight (and above)") . "';
    var PRICE_AND_ABOVE_LABEL = '" . __("Price (and above)") . "';
    
    var SHIPPING_PRICE_LABEL = '" . __("Shipping Price") . "';
    var SHIPPING_PERCENTAGE_LABEL = '" . __("Shipping Percentage") . "';
    var SHIPPING_FIXED_AMOUNT_LABEL = '" . __("Add fixed amount to price") . "';

    var COD_SURCHARGE_FIXED_LABEL = '" . __("Fixed Cash On Delivery Surcharge Amount") . "';
    var COD_SURCHARGE_PERCENTAGE_LABEL = '" . __("Cash On Delivery Surcharge Percentage") . "';
    
    var COD_MIN_SURCHARGE_LABEL = '" . __("Minimum COD Surcharge") . "';
    
    var PRICE_AND_ABOVE_NOTE = '" . __("Enter the starting price for this rate in the base currency of website. This rate will apply to orders whose subtotal (excluding shipping) is greater or equal to this price. Only include the sales tax/VAT in this price if you have configured shipping prices to include it (see System->Configuration->Sales->Tax->Calulation Settings->Shipping Prices).") . "';
    var WEIGHT_AND_ABOVE_NOTE = '" . __("Enter the starting weight in kg for this rate.") . "';
";
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array($this->_objectId => $this->getRequest()->getParam($this->_objectId)));
    }
}
