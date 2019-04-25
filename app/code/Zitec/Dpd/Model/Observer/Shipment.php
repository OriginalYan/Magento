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

namespace Zitec\Dpd\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
abstract class Shipment
{
    /**
     *
     * @var boolean
     */
    protected $_isProcessed = false;

    /**
     *
     * @var \Magento\Sales\Model\Order\Address
     */
    protected $_shippingAddress = null;

    /**
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order = null;

    /**
     *
     * @var \Zitec_Dpd_Api_Shipment_Save_Response
     */
    protected $_response = null;

    /**
     *
     * @var \Zitec_Dpd_Api_Shipment_Save
     */
    protected $_call = null;

    /**
     *
     * @var string
     */
    protected $_labelPdfStr = null;


    /**
     *
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $_shipment = null;

    /**
     *
     */
    protected $_isOrderShipmentNew = false;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $salesOrderShipmentTrackFactory;

    /**
     * @var \Zitec\Dpd\Model\Dpd\ShipFactory
     */
    protected $dpdDpdShipFactory;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $salesOrderAddressFactory;

    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    protected $dpdWsHelper;

    /**
     * @var \Zitec\Dpd\Model\PackedShipment\PackedShipmentFactory
     */
    protected $packedShipmentPackedShipmentFactory;
    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockFactory;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $salesOrderShipmentTrackFactory,
        \Zitec\Dpd\Model\Dpd\ShipFactory $dpdDpdShipFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Sales\Model\Order\AddressFactory $salesOrderAddressFactory,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper,
        \Zitec\Dpd\Model\PackedShipment\PackedShipmentFactory $packedShipmentPackedShipmentFactory,
        \Magento\Cms\Model\BlockFactory $blockFactory
    ) {
        $this->packedShipmentPackedShipmentFactory = $packedShipmentPackedShipmentFactory;
        $this->request = $request;
        $this->dpdHelper = $dpdHelper;
        $this->salesOrderShipmentTrackFactory = $salesOrderShipmentTrackFactory;
        $this->dpdDpdShipFactory = $dpdDpdShipFactory;
        $this->layout = $layout;
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
        $this->dpdWsHelper = $dpdWsHelper;
        $this->blockFactory = $blockFactory;
    }

    /**
     * is not used anymore - to copy the file we use config.xml
     *
     * @param \Magento\Framework\DataObject $observer
     *
     * @return
     */
    public function postcodeAddressConvertToOrder($observer)
    {
        if ($observer->getEvent()->getAddress()->getValidPostcode()) {
            $observer->getEvent()->getOrder()
                ->setValidPostcode($observer->getEvent()->getAddress()->getValidPostcode());
        }
        if ($observer->getEvent()->getAddress()->getAutoPostcode()) {
            $observer->getEvent()->getOrder()
                ->setAutoPostcode($observer->getEvent()->getAddress()->getAutoPostcode());
        }

        return $this;
    }


}
