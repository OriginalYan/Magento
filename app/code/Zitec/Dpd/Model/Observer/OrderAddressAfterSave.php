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
class OrderAddressAfterSave implements ObserverInterface
{

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory
     */
    protected $dpdMysql4DpdShipCollectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $salesOrderShipmentFactory;

    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    protected $dpdWsHelper;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;

    public function __construct(
        \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory $dpdMysql4DpdShipCollectionFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $salesOrderShipmentFactory,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->dpdMysql4DpdShipCollectionFactory = $dpdMysql4DpdShipCollectionFactory;
        $this->salesOrderShipmentFactory = $salesOrderShipmentFactory;
        $this->dpdWsHelper = $dpdWsHelper;
        $this->dpdHelper = $dpdHelper;
    }
    /**
     * Communicate the updated address to DPD.
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->dpdHelper->isAdmin()) {
            return;
        }


        $address = $observer->getEvent()->getAddress();
        /* @var $address Mage_Sales_Model_Order_Address */
        if ($address->getAddressType() != 'shipping') {
            return;
        }

        $order = $address->getOrder();
        /* @var $order Mage_Sales_Model_Order */
        if (!$this->dpdHelper->moduleIsActive($order->getStore())) {
            return;
        }

        if (!$this->dpdHelper->isShippingMethodDpd($order->getShippingMethod())) {
            return;
        }

        if (!$this->_communicateAddresUpdateToDpd($address)) {
            return;
        }

        $this->dpdHelper->addNotice(__("The new shipping address for shipments associated with this order have been communicated successfully to DPD."));

    }

    /**
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _communicateAddresUpdateToDpd(\Magento\Sales\Model\Order\Address $address)
    {

        $shipsCollectionForOrder = $this->dpdMysql4DpdShipCollectionFactory->create();
        /* @var $shipsCollectionForOrder Zitec_Dpd_Model_Mysql4_Dpd_Ship_Collection */
        $shipsCollectionForOrder->setOrderFilter($address->getParentId());
        if (!$shipsCollectionForOrder->count()) {
            return false;
        }
        foreach ($shipsCollectionForOrder as $ship) {
            /* @var $ship Zitec_Dpd_Model_Dpd_Ship */
            $dpdShipment = unserialize($ship->getSaveShipmentCall());
            /* @var $dpdShipmnent Zitec_Dpd_Api_Shipment_Save */
            try {
                $response = $dpdShipment->setReceiverAddress($address)
                    ->execute();
                /* @var $response Zitec_Dpd_Api_Shipment_Save_Response */
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(sprintf(__('An error occurred updating the shipping address details with DPD: <br /> "%1"', $e->getMessage())));
            }
            if ($response->hasError()) {
                throw new \Magento\Framework\Exception\LocalizedException(sprintf(__('DPD could not update the shipment address. The following error was returned: <br /> "%1: %1"'), $response->getErrorCode(), $response->getErrorText()));
            }

            try {
                $labelPdfStr = $this->dpdWsHelper->getNewPdfShipmentLabelsStr($response->getDpdShipmentId(), $response->getDpdShipmentReferenceNumber());
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(sprintf(__('An error occurred retrieving the updated shipping labels from DPD. <br />"s%"'), $e->getMessage()));
            }

            $ship->setSaveShipmentCall(serialize($dpdShipment))
                ->setSaveShipmentResponse(serialize($response))
                ->setShippingLabels(base64_encode($labelPdfStr))
                ->save();

            $this->salesOrderShipmentFactory->create()
                ->load($ship->getShipmentId())
                ->setShippingLabel($labelPdfStr)
                ->save();
        }

        return true;
    }
}


