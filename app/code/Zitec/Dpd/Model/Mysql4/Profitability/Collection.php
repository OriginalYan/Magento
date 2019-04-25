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

namespace Zitec\Dpd\Model\Mysql4\Profitability;

/**
 * Report for price vs cost of shipping.
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        parent::_construct();

        $this->_init(
            \Magento\Sales\Model\Order::class,
            \Magento\Sales\Model\ResourceModel\Order\Collection::class
        );
    }

    public function addReportFields()
    {
        $this->getSelect()
            ->columns(['zitec_shipping_profit' => new \Zend_Db_Expr("main_table.base_shipping_amount - main_table.zitec_total_shipping_cost")])
            ->joinLeft(
                ['zitec_shipping_address' => $this->getTable('sales_order_address')],
                "main_table.entity_id = zitec_shipping_address.parent_id AND zitec_shipping_address.address_type = 'shipping' ",
                [
                    'zitec_shipping_name'       => new \Zend_Db_Expr("CONCAT(zitec_shipping_address.lastname, ', ', zitec_shipping_address.firstname) "),
                    'zitec_shipping_postcode'   => "zitec_shipping_address.postcode",
                    'zitec_shipping_region'     => "zitec_shipping_address.region",
                    'zitec_shipping_country_id' => "zitec_shipping_address.country_id"
                ]);

        return $this;
    }

    public function setStoreIds($storeIds)
    {
        if ($storeIds) {
            $this->addFieldToFilter('store_id', ['in' => (array)$storeIds]);
        }

        return $this;
    }
}

