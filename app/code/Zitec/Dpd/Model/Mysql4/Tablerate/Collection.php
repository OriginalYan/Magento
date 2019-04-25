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

namespace Zitec\Dpd\Model\Mysql4\Tablerate;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     *
     * @var array
     */
    protected $_map = null;

    /**
     * directory/country table name
     *
     * @var string
     */
    protected $_countryTable;

    /**
     * directory/country_region table name
     *
     * @var string
     */
    protected $_regionTable;


    /**
     * Define resource model
     *
     */
    protected function _construct()
    {
        $this->_init(
            \Zitec\Dpd\Model\Tablerate\Tablerate::class,
            \Zitec\Dpd\Model\Mysql4\Tablerate::class
        );

        //$this->_init('zitec_tablerates/tablerate');
        $this->_map = $this->getResource()->getLogicalDbFieldNamesMap();
        $this->_countryTable = $this->getTable('directory_country');
        $this->_regionTable  = $this->getTable('directory_country_region');
    }

    /**
     * Initialize select, add country iso3 code and region name
     *
     * @return void
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->getResource()->prepareSelectColumns($this->_select);

        $this->_select->joinLeft(
            array('country_table' => $this->_countryTable), "country_table.country_id = main_table.{$this->_map['dest_country_id']}", array('dest_country' => 'iso2_code'))
            ->joinLeft(
                array('region_table' => $this->_regionTable), "region_table.region_id = main_table.{$this->_map['dest_region_id']}", array('dest_region' => 'code', 'dest_region_name' => 'default_name'));

    }
}
