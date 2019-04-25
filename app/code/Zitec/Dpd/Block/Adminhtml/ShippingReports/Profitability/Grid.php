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

namespace Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability;

use Magento\Backend\Block\Widget\Grid\Extended as ExtendedGrid;
use Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability\Renderer\Posnegcurrency;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Grid extends ExtendedGrid
{
    const SHIPPING_NAME_COLUMN = 'shipping_name';

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Profitability\CollectionFactory
     */
    private $shippingReportsMysql4ProfitabilityCollectionFactory;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Zitec\Dpd\Model\Mysql4\Profitability\CollectionFactory $shippingReportsMysql4ProfitabilityCollectionFactory
    ) {
        parent::__construct($context, $backendHelper);

        $this->dataObjectFactory = $dataObjectFactory;

        $this->shippingReportsMysql4ProfitabilityCollectionFactory = $shippingReportsMysql4ProfitabilityCollectionFactory;

        $this->setId('zitec_DpdProfitabilityGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setSubReportSize(false);
        $this->setCountTotals(true);
        $this->setUseAjax(true);
    }

    protected function _prepareColumns()
    {
        $this->addColumn('order_ref', [
            'header'   => __('Order #'),
            'align'    => 'right',
            'index'    => 'increment_id',
            'renderer' => \Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability\Renderer\Orderref::class
        ]);

        $this->addColumn('store_id', [
            'header' => __('Purchase Point'),
            'index' => 'store_id',
            'type' => 'store',
            'store_view' => true]
        );

        $this->addColumn('created_at', [
            'header' => __('Purchased On'),
            'index'  => 'created_at',
            'type'   => 'datetime',
        ]);

        $this->addColumn(self::SHIPPING_NAME_COLUMN, [
            'header' => __('Ship to Name'),
            'index'  => 'zitec_shipping_name',
        ]);

        $this->addColumn('shipping_postcode', [
            'header' => __('Postcode'),
            'index'  => 'zitec_shipping_postcode',
        ]);

        $this->addColumn('shipping_region', [
            'header' => __('Region'),
            'index'  => 'zitec_shipping_region',
        ]);

        $this->addColumn('zitec_shippingreports', [
            'header' => __('Country'),
            'index'  => 'zitec_shipping_country_id',
            'type'   => 'country'
        ]);

        $this->addColumn('grand_total', [
            'header'   => __('Order total'),
            'index'    => 'base_grand_total',
            'type'     => 'currency',
            'total'    => 'sum',
            'currency' => 'base_currency_code',
        ]);

        $this->addColumn('shipping_amount', [
            'header'   => __('Shipping Price'),
            'index'    => 'base_shipping_amount',
            'type'     => 'currency',
            'total'    => 'sum',
            'currency' => 'base_currency_code',
        ]);

        $this->addColumn('total_shipping_cost', [
            'header'   => __('Total Shipping Cost'),
            'index'    => 'zitec_total_shipping_cost',
            'type'     => 'currency',
            'total'    => 'sum',
            'currency' => 'base_currency_code',
        ]);

        $this->addColumn('shipping_profit', [
            'header'   => __('Shipping Profit/Loss'),
            'index'    => 'zitec_shipping_profit',
            'total'    => 'sum',
            'align'    => 'right',
            'renderer' => Posnegcurrency::class,
        ]);

        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportXLS', __('Excel XLS'));

        return parent::_prepareColumns();
    }

    protected function _prepareCollection()
    {
        $collection = $this->shippingReportsMysql4ProfitabilityCollectionFactory->create();
        $collection->addReportFields();
        $this->setCollection($collection);

        parent::_prepareCollection();

        $this->_computeTotals();

        return $this;
    }

    protected function _addColumnFilterToCollection($column)
    {
        switch($column->getId()) {
            case self::SHIPPING_NAME_COLUMN:
                $this->getCollection()->addFieldToFilter(new \Zend_Db_Expr('CONCAT(zitec_shipping_address.lastname, \', \', zitec_shipping_address.firstname)'), $column->getFilter()->getCondition());
                break;
            default:
                parent::_addColumnFilterToCollection($column);
                break;
        }

        return $this;
    }

    /**
     * Add new export type to grid
     *
     * @param   string $url
     * @param   string $label
     *
     * @return  \Magento\Backend\Block\Widget\Grid
     */
    public function addExportType($url, $label)
    {
        $this->_exportTypes[] = $this->dataObjectFactory->create(
            [
                'data' => [
                    'url'   => $this->getUrl($url,
                        [
                            '_current' => true,
                            'filter'   => $this->getParam($this->getVarNameFilter(), null)
                        ]
                    ),
                    'label' => $label
                ]
            ]
        );

        return $this;
    }

    /**
     * Retrieve grid as CSV
     *
     * @return unknown
     */
    public function getCsv()
    {
        $csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();

        $data = [];
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $data[] = '"' . $column->getExportHeader() . '"';
            }
        }
        $csv .= implode(',', $data) . "\n";

        foreach ($this->getCollection() as $item) {
            $data = [];
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(['"', '\\'], ['""', '\\\\'], $column->getRowFieldExport($item)) . '"';
                }
            }
            $csv .= implode(',', $data) . "\n";
        }

        if ($this->getCountTotals()) {
            $data = [];
            $j = 0;
            foreach ($this->_columns as $column) {
                if ($j == 0) {
                    $data[] = '"' . $this->getTotalsText() . '"';
                } else {
                    if (!$column->getIsSystem()) {
                        //$data[] = '"'.str_replace('"', '""', $column->getRowField($this->getTotals())).'"';
                        $data[] = '"' . str_replace(['"', '\\'], ['""', '\\\\'],
                                $column->getRowFieldExport($this->getTotals())) . '"';
                    }
                }
                $j++;
            }
            $csv .= implode(',', $data) . "\n";
        }

        return $csv;

    }

    /**
     * Retrieve grid as Excel Xml
     *
     * @return unknown
     */
    public function getExcel($filename = '')
    {

        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();
        $headers = [];
        $data = [];
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $headers[] = $column->getHeader();
            }
        }
        $data[] = $headers;

        foreach ($this->getCollection() as $item) {
            $row = [];
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $row[] = $column->getRowField($item);
                }
            }
            $data[] = $row;
        }

        if ($this->getCountTotals()) {

            $j = 0;
            $row = [];
            foreach ($this->_columns as $column) {
                if ($j == 0) {
                    $row[] = __($this->getTotalText());
                } elseif (!$column->getIsSystem()) {
                    $row[] = $column->getRowField($this->getTotals());
                }
                $j++;
            }
            $data[] = $row;
        }

        $xmlObj = $this->create();
        $xmlObj->setVar('single_sheet', $filename);
        $xmlObj->setData($data);
        $xmlObj->unparse();

        return $xmlObj->getData();

    }

    public function getSubtotalText()
    {
        return __('Subtotal');
    }

    public function getTotalText()
    {
        return __('Totals');
    }

    public function getEmptyText()
    {
        return __('No records found for this period.');
    }

    protected function _computeTotals()
    {
        $totals = $this->dataObjectFactory->create();

        foreach ($this->getCollection() as $item) {
            foreach ($this->getColumns() as $col) {
                if ($col->getTotal()) {
                    $fieldName = $col->getIndex();
                    $subTotal = $totals->getData($fieldName);
                    $subTotal = $subTotal ? $subTotal : 0;
                    $subTotal += $item->getData($fieldName);
                    $totals->setData($fieldName, $subTotal);
                }
            }
        }

        $collectionSize = $this->getCollection()->getSize();
        if ($collectionSize) {
            foreach ($this->getColumns() as $col) {
                $fieldName = $col->getIndex();
                if ($col->getTotal() == 'avg') {
                    $avg = $totals->getData($fieldName) / $collectionSize;
                    $totals->setData($fieldName, $avg);
                }
                if ($col->getType() == 'currency') {
                    $total = $totals->getData($fieldName);
                    $totals->setData($fieldName, $total);
                }
            }
        }

        $this->setTotals($totals);
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid');
    }

    public function getRowUrl($row)
    {
        return false;
    }
}


