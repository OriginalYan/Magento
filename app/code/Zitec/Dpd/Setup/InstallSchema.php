<?php

namespace Zitec\Dpd\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Quote\Setup\QuoteSetup;
use Magento\Sales\Setup\SalesSetup;
use Psr\Log\LoggerInterface;
use Zitec\Dpd\Helper\Postcode\Search;

class InstallSchema implements InstallSchemaInterface
{
    /** @var SchemaSetupInterface */
    private $setup;

    /**
     * @var SalesSetup
     */
    private $salesSetup;
    /**
     * @var \Magento\Quote\Setup\QuoteSetup
     */
    private $quoteSetup;
    /**
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    private $postcodeSearchHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * InstallSchema constructor.
     *
     * @param \Magento\Sales\Setup\SalesSetup $salesSetup
     * @param \Magento\Quote\Setup\QuoteSetup $quoteSetup
     * @param \Zitec\Dpd\Helper\Postcode\Search $postcodeSearchHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SalesSetup $salesSetup,
        QuoteSetup $quoteSetup,
        Search $postcodeSearchHelper,
        LoggerInterface $logger
    ) {
        $this->salesSetup = $salesSetup;
        $this->quoteSetup = $quoteSetup;
        $this->postcodeSearchHelper = $postcodeSearchHelper;
        $this->logger = $logger;
    }

    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->setup = $setup;

        $setup->startSetup();

        $this->createTables();

        $this->addAttributes();

        $this->addColumns();

        $this->addOrderStatus();

        $this->installPostcodes();

        $setup->endSetup();
    }

    private function createTables()
    {
        $setup = $this->setup;

        $dpdShipsTable = $setup->getTable('zitec_dpd_ships');
        if ($setup->getConnection()->isTableExists($dpdShipsTable) !== true) {
            $setup->run(
                "CREATE TABLE {$dpdShipsTable} (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `shipment_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                    `order_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                    `save_shipment_call` MEDIUMTEXT NULL,
                    `save_shipment_response` MEDIUMTEXT NULL,
                    `shipping_labels` MEDIUMTEXT NULL,
                    `manifest_id` INT(11) NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );
        } else {
            $this->logger->warning('Table ' . $dpdShipsTable . ' already exists!');
        }

        $dpdTablerateTable = $setup->getTable('zitec_dpd_tablerate');
        if ($setup->getConnection()->isTableExists($dpdTablerateTable) !== true) {
            $setup->run(
                "CREATE TABLE `{$dpdTablerateTable}` (
                    `pk` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `website_id` INT(11) NOT NULL DEFAULT '0',
                    `dest_country_id` VARCHAR(4) NOT NULL DEFAULT '0',
                    `dest_region_id` INT(10) NOT NULL DEFAULT '0',
                    `dest_zip` VARCHAR(10) NOT NULL DEFAULT '',
                    `weight` DECIMAL(12,4) NOT NULL DEFAULT '0.0000',
                    `price` VARCHAR(10) NOT NULL DEFAULT '0.0000',
                    `method` VARCHAR(8) NOT NULL DEFAULT '0',
                    `markup_type` VARCHAR(5) NOT NULL DEFAULT '0',
                    `cashondelivery_surcharge` VARCHAR(20) NULL DEFAULT NULL,
                    `price_vs_dest` INT(10) NOT NULL DEFAULT '0',
                    `cod_min_surcharge` DECIMAL(12,4) NULL DEFAULT NULL,
                    PRIMARY KEY (`pk`),
                    UNIQUE INDEX `dest_country` (`website_id`, `dest_country_id`, `dest_region_id`, `dest_zip`, `weight`, `method`, `price_vs_dest`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );
        } else {
            $this->logger->warning('Table ' . $dpdTablerateTable . ' already exists!');
        }

        $dpdPickupOrderTable = $setup->getTable('zitec_dpd_pickup_order');
        if ($setup->getConnection()->isTableExists($dpdPickupOrderTable) !== true) {
            $setup->run(
                "CREATE TABLE `{$dpdPickupOrderTable}` (
                    `entity_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `reference` VARCHAR(255) NOT NULL,
                    `dpd_id` INT(11) NOT NULL,
                    `pickup_date` DATETIME NOT NULL,
                    `pickup_time_from` DATETIME NOT NULL,
                    `pickup_time_to` DATETIME NOT NULL,
                    `call_data` MEDIUMTEXT NOT NULL,
                    `response_data` MEDIUMTEXT NOT NULL,
                    PRIMARY KEY (`entity_id`),
                    UNIQUE INDEX `reference` (`reference`),
                    INDEX `pickup_date` (`pickup_date`, `pickup_time_from`, `pickup_time_to`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB"
            );
        } else {
            $this->logger->warning('Table ' . $dpdPickupOrderTable . ' already exists!');
        }

        $dpdManifestTable = $setup->getTable('zitec_dpd_manifest');
        if ($setup->getConnection()->isTableExists($dpdManifestTable) !== true) {
            $setup->run(
                "CREATE TABLE `{$dpdManifestTable}` (
                    `manifest_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `manifest_ref` VARCHAR(30) NOT NULL,
                    `manifest_dpd_id` VARCHAR(30) NULL DEFAULT NULL,
                    `manifest_dpd_name` VARCHAR(30) NULL DEFAULT NULL,
                    `pdf` MEDIUMTEXT NOT NULL,
                    PRIMARY KEY (`manifest_id`)
                )
                COLLATE='utf8_general_ci'
                ENGINE=InnoDB;"
            );
        } else {
            $this->logger->warning('Table ' . $dpdManifestTable . ' already exists!');
        }
    }

    private function addAttributes()
    {
        //ADD ATTRIBUTES
        $this->salesSetup
            ->addAttribute('creditmemo', 'zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('creditmemo', 'zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('creditmemo', 'base_zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('creditmemo', 'base_zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('invoice', 'zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('invoice', 'base_zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('invoice', 'zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('invoice', 'base_zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('order', 'zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('order', 'base_zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('order', 'zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('order', 'base_zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('order', 'zitec_total_shipping_cost', ['type' => Table::TYPE_DECIMAL, 'grid' => true, 'default' => 0])
            ->addAttribute('order_address', 'valid_auto_postcode', ['type' => Table::TYPE_SMALLINT])
            ->addAttribute('order_address', 'auto_postcode', ['type' => Table::TYPE_TEXT, 'length' => 10])
            ->addAttribute('shipment', 'zitec_dpd_pickup_id', ['type' => Table::TYPE_INTEGER])
            ->addAttribute('shipment', 'zitec_dpd_pickup_time', ['type' => Table::TYPE_DATETIME, "grid" => true])
            ->addAttribute('shipment', 'zitec_dpd_manifest_closed', ['type' => Table::TYPE_INTEGER, "grid" => true, "default" => 0])
            ->addAttribute('shipment', 'zitec_shipping_cost', ['type' => Table::TYPE_INTEGER, "grid" => true, "default" => 0])
        ;

        $this->quoteSetup
            ->addAttribute('quote_address', 'zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('quote_address', 'base_zitec_dpd_cashondelivery_surcharge', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('quote_address', 'zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('quote_address', 'base_zitec_dpd_cashondelivery_surcharge_tax', ['type' => Table::TYPE_DECIMAL])
            ->addAttribute('quote_address', 'valid_auto_postcode', ['type' => Table::TYPE_SMALLINT])
            ->addAttribute('quote_address', 'auto_postcode', ['type' => Table::TYPE_TEXT, 'length' => 10])
        ;
    }

    private function addOrderStatus()
    {
        $setup = $this->setup;

        //CREATE NEW ORDER STATUS
        $newOrderStatus = 'zitec_dpd_pending_cashondelivery';
        $orderStatusTable = $setup->getTable('sales_order_status');

        //if the new order status already exists, delete it
        $setup->getConnection()->delete($orderStatusTable, "`status` = '" . $newOrderStatus . "'");

        //insert new order status
        $setup->getConnection()->insertArray(
            $orderStatusTable,
            [
                'status',
                'label',
            ],
            [
                [
                    'status' => $newOrderStatus,
                    'label'  => 'DPD Pending Cash On Delivery',
                ],
            ]
        );

        //MAP NEW ORDER STATUS TO AN ORDER STATE
        $statusStateTable = $setup->getTable('sales_order_status_state');

        //if the new order status already exists, delete it
        $setup->getConnection()->delete($statusStateTable, "`status` = '" . $newOrderStatus . "'");

        $setup->getConnection()->insertArray(
            $statusStateTable,
            [
                'status',
                'state',
                'is_default'
            ],
            [
                [
                    'status'     => $newOrderStatus,
                    'state'      => 'pending_payment',
                    'is_default' => 0,
                ],
            ]
        );
    }

    private function addColumns()
    {
        $setup = $this->setup;

        $shipmentTable = $setup->getTable('sales_shipment');
        $shipmentTrackTable = $setup->getTable('sales_shipment_track');

        if (!$setup->getConnection()->tableColumnExists($shipmentTable, 'shipping_label')) {
            $setup->getConnection()->addColumn($shipmentTable, 'shipping_label', 'LONGBLOB');
        }

        if (!$setup->getConnection()->tableColumnExists($shipmentTrackTable, 'track_number')) {
            $setup->getConnection()->addColumn($setup->getTable('sales_shipment_track'), 'track_number', 'TEXT');
        }
    }

    private function installPostcodes()
    {
        $this->postcodeSearchHelper->getSearchPostcodeModel()->installPostcodeDatabase();
    }
}
