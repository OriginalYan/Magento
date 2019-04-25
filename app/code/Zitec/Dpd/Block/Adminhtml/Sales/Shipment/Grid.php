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

namespace Zitec\Dpd\Block\Adminhtml\Sales\Shipment;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Grid extends Column
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        array $components = [],
        array $data = [],
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dpdHelper = $dpdHelper;

        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->escaper = $escaper;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                if (!$item['manifest_id']) {
                    continue;
                }
                $manifestUrl = $this->dpdHelper->getDownloadManifestUrl($item['manifest_id']);

                // $this->getData('name') returns the name of the column so in this case it would return export_status
                $item[$this->getData('name')] = '<a href="' . $manifestUrl . '">' . $this->escaper->escapeHtml($item['manifest_ref']) . '</a>';
            }
        }

        return $dataSource;
    }

    /**
     * Prepare and add columns to grid
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    public function prepare()
    {
        parent::prepare();
        return;
        if ($this->dpdHelper->moduleIsActive()) {
            $this->addColumn('zitec_dpd_manifest', [
                'header'                    => __('Manifest'),
                'index'                     => 'zitec_manifest_id',
                'type'                      => 'text',
                'renderer'                  => 'Zitec_Dpd_Block_Adminhtml_Sales_Shipment_Grid_Renderer_Manifest',
                'filter_condition_callback' => [$this, '_filterManifesto'],
            ]);

            $this->addColumnsOrder('zitec_dpd_manifest_closed', 'total_qty');

            /*
             * removed column from frontend temporary
             *
            $this->addColumn('zitec_dpd_pickup_time', array(
                'header' => __('DPD Pickup'),
                'index'  => 'zitec_dpd_pickup_time',
                'type'   => 'text',
            ));
            $this->addColumnsOrder('zitec_dpd_pickup_time', 'zitec_dpd_manifest');
            */

        }

        return parent::_prepareColumns();


    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    public function getCollection()
    {
        $collection = parent::getCollection();
        if ($collection && !$this->getIsI4ShipsJoined()) {
            /* @var $collection Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection */
            $resource = $this->resourceConnection;
            /* @var $resource Mage_Core_Model_Resource */
            $shipsTableName = $resource->getTableName('zitec_dpd_ships');
            $manifestTableName = $resource->getTableName('zitec_dpd_manifest');

            $collection->getSelect()
                ->joinLeft(['ships' => $shipsTableName], 'ships.shipment_id = main_table.entity_id',
                    ['zitec_manifest_id' => 'ships.manifest_id'])
                ->joinLeft(['manifest' => $manifestTableName], 'manifest.manifest_id = ships.manifest_id',
                    ['zitec_manifest_ref' => 'manifest.manifest_ref']);
            $this->setIsI4ShipsJoined(true);
        }

        return $collection;
    }

    /**
     *
     * @param Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection $collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     *
     * @return \Zitec_Dpd_Block_Adminhtml_Sales_Shipment_Grid
     */
    protected function _filterManifesto($collection, $column)
    {
        $manifestRef = $column->getFilter()->getCondition();
        if ($manifestRef) {
            $resource = $this->resourceConnection;
            /* @var $resource Mage_Core_Model_Resource */
            $whereClause = $resource->getConnection("core_read")
                ->quoteInto("manifest.manifest_ref like ? ", $manifestRef);
            $collection->getSelect()
                ->where($whereClause);
            $debug = (string)$collection->getSelect();
        }

        return $this;
    }
}
