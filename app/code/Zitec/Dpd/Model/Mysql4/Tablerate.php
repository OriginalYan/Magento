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

namespace Zitec\Dpd\Model\Mysql4;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Zitec\Dpd\Helper\Tablerate\Data;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Tablerate extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Data
     */
    protected $tableRatesHelper;

    public function __construct(
        Context $context,
        Data $tableRatesHelper,
        $connectionName = null
    ) {
        $this->tableRatesHelper = $tableRatesHelper;
        parent::__construct(
            $context,
            $connectionName
        );
    }


    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init($this->tableRatesHelper->getTableratesDbTable(), $this->getDbTableIdField());
    }

    /**
     *
     * @param string                   $field
     * @param mixed                    $value
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $this->prepareSelectColumns($select, $this->getTable($this->tableRatesHelper->getTableratesDbTable()));

        return $select;
    }

    /**
     *
     * @return string
     */
    public function getDbTableIdField()
    {
        $map = $this->getLogicalDbFieldNamesMap();

        return $this->getDbTableFieldName($map['pk']);
    }

    /**
     * You may tablerates table for shipping method has a column whose name is distanto than normal for extensions.
     * Must set up the columns and in the config shipping method.
     * Eg the id column is usually called 'pk' but if something else (eg "entity_id"
     * will in the config module shipping method this:
     * carriers / carrier_code / zitec_tablerates / db_table_field_names / pk / entity_id
     *
     *
     * @param string $logicalName
     *
     * @return string
     */
    public function getDbTableFieldName($logicalName)
    {
        $mappedFieldName = $this->tableRatesHelper->getCarrierConfigDbTableFieldName($logicalName);

        return $mappedFieldName ? $mappedFieldName : $logicalName;
    }

    /**
     *
     * @return array
     */
    public function getLogicalDbFieldNamesMap()
    {
        $logicalDbFieldNames = array(
            'pk',
            'website_id',
            'dest_country_id',
            'dest_region_id',
            'dest_zip',
            'weight_price',
            'price_vs_dest',
            'price',
            'method',
            'product',
            'markup_type',
            'cashondelivery_surcharge',
            'cod_min_surcharge'
        );

        $map = array();
        foreach ($logicalDbFieldNames as $logicalName) {
            $map[$logicalName] = $this->getDbTableFieldName($logicalName);
        }

        return $map;
    }

    /**
     *
     * @param \Zend_Db_Select $select
     *
     * @return \\Zend_Db_Select
     */
    public function prepareSelectColumns(\Zend_Db_Select $select, $table = 'main_table')
    {
        $map = $this->getLogicalDbFieldNamesMap();

        $pricePercentageColName = $map['price'];
        if ($this->tableRatesHelper->supportsMarkup()) {
            $markupColumnName = $map['markup_type'];
            $select->columns(array('shipping_price' => new \Zend_Db_Expr("IF ({$table}.{$markupColumnName} = '0', {$table}.{$pricePercentageColName}, NULL)")));
            $select->columns(array('shipping_percentage' => new \Zend_Db_Expr("IF ({$table}.{$markupColumnName} = '1', {$table}.{$pricePercentageColName}, NULL)")));
            $select->columns(array('shipping_price_grid' => new \Zend_Db_Expr("IF ({$table}.{$markupColumnName} = '0' and {$table}.{$pricePercentageColName} >= 0, {$table}.{$pricePercentageColName}, NULL)")));
            $select->columns(array('shipping_percentage_grid' => new \Zend_Db_Expr("IF ({$table}.{$markupColumnName} = '1' and {$table}.{$pricePercentageColName} >= 0, {$table}.{$pricePercentageColName}, NULL)")));
            $select->columns(array('addition_amount_grid' => new \Zend_Db_Expr("IF ({$table}.{$markupColumnName} = '2' and {$table}.{$pricePercentageColName} >= 0, {$table}.{$pricePercentageColName}, NULL)")));
        } else {

            $select->columns(array('shipping_price' => "{$table}.{$pricePercentageColName}"));
            $select->columns(array('shipping_percentage' => null));
            $select->columns(array('shipping_price_grid' => new \Zend_Db_Expr("IF({$table}.{$pricePercentageColName} >= 0, {$table}.{$pricePercentageColName}, NULL)")));
            $select->columns(array('shipping_percentage_grid' => null));
            $select->columns(array('addition_amount_grid' => null));

        }

        $select->columns(array('is_enabled_grid' => new \Zend_Db_Expr("IF ({$table}.{$pricePercentageColName} >= 0, 1, 0)")));

        $weightPriceColName = $map['weight_price'];
        if ($this->tableRatesHelper->supportsPriceVsDest()) {
            $priceVsDestColName = $map['price_vs_dest'];
            $select->columns(array('weight_and_above' => new \Zend_Db_Expr("IF ({$table}.{$priceVsDestColName} = '0' , {$table}.{$weightPriceColName}, NULL)")));
            $select->columns(array('price_and_above' => new \Zend_Db_Expr("IF ({$table}.{$priceVsDestColName} <> '0' , {$table}.{$weightPriceColName}, NULL)")));
        } else {
            $select->columns(array('weight_and_above' => "{$table}.{$weightPriceColName}"));
            $select->columns(array('price_and_above' => null));
        }

        if ($this->tableRatesHelper->supportsCashOnDelivery()) {
            $cashOnDeliverySurchargeColName = $map['cashondelivery_surcharge'];
            $select->columns(array('cod_surcharge_price' => new \Zend_Db_Expr("IF(not ISNULL({$table}.{$cashOnDeliverySurchargeColName}) and RIGHT({$table}.{$cashOnDeliverySurchargeColName}, 1) <> '%' , CAST({$table}.{$cashOnDeliverySurchargeColName} AS DECIMAL(10,2)), NULL) ")));
            $select->columns(array('cod_surcharge_percentage' => new \Zend_Db_Expr("IF(RIGHT({$table}.{$cashOnDeliverySurchargeColName}, 1) = '%', CAST(LEFT({$table}.{$cashOnDeliverySurchargeColName}, LENGTH({$table}.{$cashOnDeliverySurchargeColName}) - 1) AS DECIMAL(10,2)), NULL)")));
        } else {
            $select->columns(array('cod_surcharge_price' => null, 'cod_surcharge_percentage' => null));
        }

        return $select;
    }
}
