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

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended as ExtendedGrid;
use Magento\Store\Model\StoreManagerInterface;
use Zitec\Dpd\Model\Mysql4\Carrier\Tablerate\Collection;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class GridExport extends ExtendedGrid
{

    /**
     * Website filter
     *
     * @var int
     */
    protected $_websiteId;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Zitec\Dpd\Model\Mysql4\Tablerate\Collection
     */
    private $dpdMysql4CarrierTablerateCollection;

    public function _construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        $this->dpdMysql4CarrierTablerateCollection = $objectManager->get(Collection::class);

        $this->setId('zitec_dpd_shippingTablerateGrid');
        $this->_exportPageSize = 10000;

        parent::_construct(); // TODO: Change the autogenerated stub
    }


    /**
     * Set current website
     *
     * @param int $websiteId
     *
     * @return \Zitec\Dpd\Block\Adminhtml\Tablerate\GridExport
     */
    public function setWebsiteId($websiteId)
    {
        $this->_websiteId = $this->storeManager->getWebsite($websiteId)->getId();

        return $this;
    }

    /**
     * Retrieve current website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        if (is_null($this->_websiteId)) {
            $this->_websiteId = $this->storeManager->getWebsite()->getId();
        }

        return $this->_websiteId;
    }

    /**
     * Prepare shipping table rate collection
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->dpdMysql4CarrierTablerateCollection;
        $collection->setWebsiteFilter($this->getWebsiteId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare table columns
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareColumns()
    {

        // 'website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'weight', 'price', 'method'
        $this->addColumn('dest_country', array(
            'header'  => __('Country'),
            'index'   => 'dest_country',
            'default' => '*',
        ));

        $this->addColumn('dest_region', array(
            'header'  => __('Region/State'),
            'index'   => 'dest_region',
            'default' => '*',
        ));

        $this->addColumn('dest_zip', array(
            'header'  => __('Zip/Postal Code'),
            'index'   => 'dest_zip',
            'default' => '*',
        ));

        $this->addColumn('weight', array(
            'header' => 'Weight / Price (and above)',
            'index'  => 'weight',
        ));

        $this->addColumn('price', array(
            'header' => __('Shipping Price/Percentage/Addition'),
            'index'  => 'shipping_price',
        ));

        $this->addColumn('Method', array(
            'header' => 'Method',
            'index'  => 'method',
        ));

        $this->addColumn('cashondelivery_surcharge', array(
            'header'  => 'Cash On Delivery Surcharge',
            'index'   => 'cashondelivery_surcharge',
            'default' => ''
        ));

        $this->addColumn('cod_min_surcharge', array(
            'header'  => 'Minimum COD Surcharge',
            'index'   => 'cod_min_surcharge',
            'default' => ''
        ));

        $this->addColumn('price_vs_dest', array(
            'header'  => 'Price vs Dest',
            'index'   => 'price_vs_dest',
            'default' => '0'
        ));


        return parent::_prepareColumns();
    }
}