<?php

namespace Zitec\Dpd\Plugin;

use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection as ShipmentGridCollection;

class AddColumnsShipmentGridCollection
{
    private $messageManager;

    private $collection;

    public function __construct(
        MessageManager $messageManager,
        ShipmentGridCollection $collection
    ) {
        $this->messageManager = $messageManager;
        $this->collection = $collection;
    }

    public function aroundGetReport(
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject,
        \Closure $proceed,
        $requestName
    ) {
        $result = $proceed($requestName);

        if ($requestName == 'sales_order_shipment_grid_data_source') {
            if ($result instanceof $this->collection) {

                $select = $this->collection->getSelect();

                $select->joinLeft(
                    ["zds" => $this->collection->getTable('zitec_dpd_ships')],
                    'main_table.entity_id = zds.shipment_id',
                    array('manifest_id')
                );

                $select->joinLeft(
                    ["zdm" => $this->collection->getTable('zitec_dpd_manifest')],
                    'zds.manifest_id = zdm.manifest_id',
                    array('manifest_id', 'manifest_ref')
                );

                return $this->collection;
            }
        }

        return $result;
    }
}
