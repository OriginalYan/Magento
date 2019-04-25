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

namespace Zitec\Dpd\Block\Adminhtml\Tablerate\Edit;

use Zitec\Dpd\Model\Tablerate\Tablerate;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Form extends \Magento\Backend\Block\Widget\Form
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Zitec\Dpd\Model\Tablerate\Source\Website
     */
    protected $tableRatesSourceWebsite;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Magento\Directory\Model\Config\Source\CountryFactory
     */
    protected $directoryConfigSourceCountryFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $directoryResourceModelRegionCollectionFactory;

    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Data
     */
    protected $tableRatesHelper;

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    public function _construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->formFactory = $objectManager->get(\Magento\Framework\Data\FormFactory::class);
        $this->tableRatesHelper = $objectManager->get(\Zitec\Dpd\Helper\Tablerate\Data::class);
        $this->registry = $objectManager->get(\Magento\Framework\Registry::class);
        $this->backendSession = $objectManager->get(\Magento\Backend\Model\Session::class);
        $this->tableRatesSourceWebsite = $objectManager->get(\Zitec\Dpd\Model\Tablerate\Source\Website::class);
        $this->directoryConfigSourceCountryFactory = $objectManager->get(\Magento\Directory\Model\Config\Source\CountryFactory::class);
        $this->directoryResourceModelRegionCollectionFactory = $objectManager->get(\Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class);

        return parent::_construct();
    }

  protected function _prepareForm()
    {
        $form = $this->formFactory->create([
            'data' => [
                'id'      => 'edit_form',
                'action' => $this->getUrl('*/*/save', ['tablerate_id' => $this->getRequest()->getParam('tablerate_id')]),
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
            ]
        ]);
        $this->setForm($form);
        $model = $this->registry->registry('tablerate_data');
        /* @var $model \Zitec\Dpd\Model\Tablerate\Tablerate */

        $fieldset = $form->addFieldset('base_fieldset', array());

        if ($model->getId()) {
            $fieldset->addField('tablerate_id', 'hidden', array(
                'name'  => 'pk',
                'value' => $model->getMappedData('pk'),
            ));
        }

        $fieldset->addField('website_id', 'select', array(
            'name'     => 'website_id',
            'label'    => __('Website'),
            'required' => true,
            'value'    => $model->getMappedData('website_id'),
            'values'   => $this->tableRatesSourceWebsite->toOptionArray()
        ));

        $destCountryId = $model->getMappedData('dest_country_id');
        $fieldset->addField('dest_country_id', 'select', array(
            'name'     => 'dest_country_id',
            'label'    => __('Dest Country'),
            'required' => false,
            'value'    => $destCountryId,
            'values'   => $this->getCountryValues()
        ));


        $fieldset->addField('dest_zip', 'text', array(
            'name'     => 'dest_zip',
            'label'    => __('Dest Zip/Postal Code'),
            'note'     => __('* or blank - matches any'),
            'required' => false,
            'value'    => $model->getMappedData('dest_zip')
        ));
        $fieldset->addField('method', 'select', array(
            'name'     => 'method',
            'label'    => __('Service'),
            'required' => true,
            'value'    => $model->getMappedData('method'),
            'values'   => $this->_getHelper()->getMethodOptions(),
            'note'     => __('For rates with this service to be available, you must enable the service from the configuration panel of the shipping method in System->Configuration->Shipping Methods')
        ));

        if ($this->_getHelper()->supportsProduct()) {
            $fieldset->addField('product', 'select', array(
                'name'     => 'product',
                'label'    => __('Product'),
                'required' => true,
                'value'    => $model->getMappedData('product'),
                'values'   => $this->_getHelper()->getProductOptions(),
                'note'     => __('For rates with this product to be available, you must enable the product from the configuration panel of the shipping method in System->Configuration->Shipping Methods')
            ));
        }

        if ($this->_getHelper()->supportsPriceVsDest()) {
            $fieldset->addField('price_vs_dest', 'select', array(
                'name'     => 'price_vs_dest',
                'label'    => __('Condition'),
                'required' => true,
                'value'    => $model->getMappedData('price_vs_dest'),
                'values'   => array('0' => __("Weight vs. Destination"), '1' => __('Price vs. Destination')),
            ));
        }


        $fieldset->addField('weight_price', 'text', array(
            'name'     => 'weight_price',
            'label'    => __('Weight (and above)'),
            'required' => true,
            'class'    => 'validate-number',
            'value'    => $model->getMappedData('weight_price'),
            'note'     => __("Enter the starting weight in kg for this rate.")
        ));

        $fieldset->addField('shipping_method_enabled', 'select', array(
            'name'     => 'shipping_method_enabled',
            'label'    => __('Enable Shipping Method'),
            'required' => true,
            'value'    => ($model->getMappedData('price') >= 0 ? '1' : '0'),
            'values'   => array('0' => __('Disabled'), '1' => __('Enabled')),
            'note'     => __('Disable the shipping method if you would like it to be unavailable for orders whose price or weight is greater or equal to the value you have indicated.')
        ));

        if ($this->_getHelper()->supportsMarkup()) {
            $fieldset->addField('markup_type', 'select', array(
                'name'     => 'markup_type',
                'label'    => __('Shipping Price Calculation'),
                'required' => true,
                'value'    => $model->getMappedData('markup_type'),
                'values'   => array('0' => __("Fixed Price"), '1' => __('Add Percentage'), '2' => __('Add Fixed amount')),
                'note'     => __("Use 'Add Percentage' if you want to calculate the shipping price by adding a percentage to price charged by the shipping carrier.")
            ));
        }

        $fieldset->addField('price', 'text', array(
            'name'     => 'price',
            'label'    => __('Shipping Price'),
            'required' => true,
            'value'    => $model->getMappedData('price'),
            'class'    => 'validate-number'
        ));

        if ($this->_getHelper()->supportsCashOnDelivery()) {
            $codOption = $model->getCashOnDeliverySurchargeOption();
            $fieldset->addField('cod_option', 'select', array(
                'name'     => 'cod_option',
                'label'    => __('Cash On Delivery Surcharge Calculation'),
                'required' => true,
                'value'    => $codOption,
                'values'   => array(
                    Tablerate::COD_NOT_AVAILABLE        => __("Cash On Delivery Not Available"),
                    Tablerate::COD_SURCHARGE_ZERO       => __("Zero Surcharge"),
                    Tablerate::COD_SURCHARGE_FIXED      => __('Fixed Surcharge'),
                    Tablerate::COD_SURCHARGE_PERCENTAGE => __('Percentage Surcharge')
                ),
            ));


            $fieldset->addField('cashondelivery_surcharge', 'text', array(
                'name'     => 'cashondelivery_surcharge',
                'label'    => __('Fixed Cash On Delivery Surcharge'),
                'required' => true,
                'value'    => $codOption == Tablerate::COD_SURCHARGE_PERCENTAGE ? $model->getData('cod_surcharge_percentage') : $model->getData('cod_surcharge_price'),
                'class'    => 'validate-number',
            ));

            if ($this->_getHelper()->supportsCodMinSurcharge()) {
                $fieldset->addField('cod_min_surcharge', 'text', array(
                    'name'     => 'cod_min_surcharge',
                    'label'    => __('Minimum COD Surcharge'),
                    'required' => false,
                    'value'    => $model->getMappedData('cod_min_surcharge'),
                    'class'    => 'validate-number',
                    'note'     => __('Optionally specify the minimum COD surcharge.')
                ));
            }
        }


        $sessionData = $this->_getSessionFormData();
        if (is_array($sessionData)) {
            $form->setValues($sessionData);
            $destRegionId  = array_key_exists('dest_region_id', $sessionData) ? $sessionData['dest_region_id'] : null;
            $destCountryId = array_key_exists('dest_country_id', $sessionData) ? $sessionData['dest_country_id'] : null;
            $this->_clearSessionFormData();
        } else {
            $destRegionId = $model->getMappedData('dest_region_id');
        }

        $fieldset->addField('dest_region_id', 'select', array(
            'name'     => 'dest_region_id',
            'label'    => __('Dest Region/State'),
            'required' => false,
            'value'    => $destRegionId,
            'values'   => $this->getRegionValues($destCountryId),
        ), 'dest_country_id');

        $form->setUseContainer(true);

        return parent::_prepareForm();
    }

    /**
     *
     * @return array
     */
    protected function _getSessionFormData()
    {
        return $this->backendSession->getTablerateData();
    }

    /**
     *
     * @return \Zitec_TableRates_Block_Adminhtml_Tablerate_Edit_Form
     */
    protected function _clearSessionFormData()
    {
        $this->backendSession->setTablerateData(null);

        return $this;
    }

    /**
     * Get country values
     *
     * @return array
     */
    protected function getCountryValues()
    {
        $countries = $this->directoryConfigSourceCountryFactory->create()->toOptionArray(false);
        if (isset($countries[0])) {
            $countries[0] = array('label' => '*', 'value' => 0);
        }

        return $countries;
    }

    /**
     * Get region values
     *
     * @return array
     */
    protected function getRegionValues($destCountryId = null)
    {
        $regions       = array(array('value' => '', 'label' => '*'));
        $model         = $this->registry->registry('tablerate_data');
        $destCountryId = isset($destCountryId) ? $destCountryId : $model->getDestCountryId();
        if ($destCountryId) {
            $regionCollection = $this->directoryResourceModelRegionCollectionFactory->create()
                ->addCountryFilter($destCountryId);
            $regions          = $regionCollection->toOptionArray();
            if (isset($regions[0])) {
                $regions[0]['label'] = '*';
            }
        }

        return $regions;
    }

    /**
     *
     * @return \Zitec\Dpd\Helper\Tablerate\Data
     */
    protected function _getHelper()
    {
        return $this->tableRatesHelper;
    }

}
