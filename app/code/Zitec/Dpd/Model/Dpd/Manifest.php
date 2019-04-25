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

namespace Zitec\Dpd\Model\Dpd;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Manifest extends \Magento\Framework\Model\AbstractModel
{

    /**
     *
     * @var array
     */
    protected $_notifications = array();

    protected $_shipsForManifest = array();

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $salesResourceModelOrderShipmentCollectionFactory;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory
     */
    protected $dpdMysql4DpdShipCollectionFactory;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    protected $dpdWsHelper;

    /**
     * @var \Zitec\Dpd\Helper\ApiFactory
     */
    protected $dpdApiFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $salesResourceModelOrderShipmentCollectionFactory,
        \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory $dpdMysql4DpdShipCollectionFactory,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper,
        \Zitec\Dpd\Helper\ApiFactory $dpdApiFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dpdApiFactory = $dpdApiFactory;
        $this->salesResourceModelOrderShipmentCollectionFactory = $salesResourceModelOrderShipmentCollectionFactory;
        $this->dpdMysql4DpdShipCollectionFactory = $dpdMysql4DpdShipCollectionFactory;
        $this->dpdHelper = $dpdHelper;
        $this->dpdWsHelper = $dpdWsHelper;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }



    public function _construct()
    {
        $this->_init('Zitec\Dpd\Model\Mysql4\Dpd\Manifest');
    }

    /**
     *
     * @param array|int|null $shipmentIds
     *
     * @return boolean
     */
    public function createManifestForShipments($shipmentIds)
    {
        $this->_clearNotifications();

        if ($shipmentIds && is_numeric($shipmentIds)) {
            $shipmentIds = array($shipmentIds);
        }

        if (!$shipmentIds || !is_array($shipmentIds)) {
            $message = __("Please select the shipments to include in the closed manifest.");
            $this->_addNotification($message);

            return false;
        }


        $shipments = $this->salesResourceModelOrderShipmentCollectionFactory->create();
        /* @var $shipments Mage_Sales_Model_Resource_Order_Shipment_Collection */
        $shipments->addFieldToFilter('entity_id', array("in" => $shipmentIds));

        $ships = $this->dpdMysql4DpdShipCollectionFactory->create();
        /* @var $ships Zitec_Dpd_Model_Mysql4_Dpd_Ship_Collection */
        $ships->filterByShipmentIds($shipmentIds);

        $shipmentsForManifest = array();
        $shipsForManifest     = array();
        $manifestParams       = null;
        foreach ($shipments as $shipment) {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            if (!$this->dpdHelper->isShippingMethodDpd($shipment->getOrder()->getShippingMethod())) {
                $message = "Could not include shipment %1 in the manifest because it is not a DPD shipment.";
                $this->_addNotification(__($message, $shipment->getIncrementId()), true);
                continue;
            }

            $ship = $ships->findByShipmentId($shipment->getId());
            /* @var $ship Zitec_Dpd_Model_Dpd_Ship */
            if (!$ship) {
                $message = "Could not include shipment %1 in the manifest because it was not communicated to DPD.";
                $this->_addNotification(__($message, $shipment->getIncrementId()), true);
                continue;
            }

            if ($this->dpdHelper->isCancelledWithDpd($shipment)) {
                $message = "Could not include shipment %1 in the manifest because it was cancelled with DPD.";
                $this->_addNotification(__($message, $shipment->getIncrementId()), true);
                continue;
            }

            if ($ship->getManifestId()) {
                $message = "Could not include shipment %1 in the manifest because it is already in a manifest.";
                $this->_addNotification(__($message, $shipment->getIncrementId()), true);
                continue;
            }

            $storeManifestParams = $this->dpdWsHelper->getManifestParams($shipment->getOrder()->getStore());
            if (isset($manifestParams) && $storeManifestParams != $manifestParams) {
                $this->_clearNotifications();
                $message = "The shipments you selected come from different stores which have different DPD connection parameters. Please select shipments which belong " .
                    "to stores that use the same DPD connection parameters.";
                $this->_addNotification(__($message));

                return false;
            } else {
                $manifestParams = $storeManifestParams;
            }

            $this->_addNotification(__('Shipment %1 was added to the manifest.', $shipment->getIncrementId()));

            $shipmentsForManifest[] = $shipment;
            $shipsForManifest[]     = $ship;
        }

        if (count($shipmentsForManifest) == 0) {
            $this->_clearNotificationErrorStyles();
            $this->_addNotification("None of the shipments selected could be included in a manifest.");

            return false;
        }

        $manifestParams['method'] = \Zitec_Dpd_Api_Configs::METHOD_MANIFEST_CLOSE;
        $dpdApi = $this->dpdApiFactory->create($manifestParams);
        $closeManifest = $dpdApi->getApiMethodObject();

        foreach ($shipmentsForManifest as $index => $shipment) {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            $ship = $shipsForManifest[$index];
            /* @var $ship Zitec_Dpd_Model_Dpd_Ship */
            $shipResponse = unserialize($ship->getSaveShipmentResponse());
            /* @var $response Zitec_Dpd_Api_Shipment_Save_Response */
            $closeManifest->addShipment($shipResponse->getDpdShipmentId(), $shipResponse->getDpdShipmentReferenceNumber());
            if ($index == 0) {
                $closeManifest->setManifestReferenceNumber($shipResponse->getDpdShipmentReferenceNumber());
            }
        }

        try {
            $closeManifest->execute();
        } catch (Exception $e) {
            $this->_clearNotifications();
            $this->_addNotification(__("An error occurred whilst requesting the manifest from DPD: %1", $e->getMessage()));

            return false;
        }

        $response = $closeManifest->getCloseManifestResponse();
        if ($response->hasError()) {
            $this->_clearNotifications();
            $this->_addNotification(__('An error occurred whilst communicating the manifest details to DPD. DPD says: "%1"', $response->getErrorText()));

            return false;
        }

        $this->setManifestRef($response->getManifestReferenceNumber());
        $this->setManifestDpdId($response->getManifestId());
        $this->setManifestDpdName($response->getManifestName());
        $this->setPdf(base64_encode($response->getPdfFile()));
        $this->setShipsForManifest($shipsForManifest);

        try {
            $this->save();

        } catch (Exception $e) {
            $this->_clearNotificationErrorStyles();
            $message = "The manifest was communicated successfully to DPD, but an error occurred whilst saving the manifest details in Magento. <br />" .
                "Please make a capture of this screen so that you have a record of the shipments included in the manifest. <br />" .

                "For you reference when communicating with DPD, the manifest details are: <br /> " .
                "Manifest Reference: %1 <br />" .
                "Manifest Name: %1 <br />" .
                "Manifest internal DPD ID: %1 <br />" .
                "The error message returned was: %1";

            $this->_addNotification(__($message, $this->getManifestRef(), $this->getManifestDpdId(), $this->getManifestDpdName(), $e->getMessage()));
            $this->dpdHelper->log(sprintf($message, $this->getManifestRef(), $this->getManifestDpdId(), $this->getManifestDpdName(), $e->getMessage()));

            return false;

        }

        return true;
    }


    /**
     *
     * @return \Zitec_Dpd_Model_Dpd_Manifest
     */
    protected function _clearNotifications()
    {
        $this->_notifications = array();

        return $this;
    }

    /**
     *
     * @param string $message
     *
     * @return \Zitec_Dpd_Model_Dpd_Manifest
     */
    protected function _addNotification($message, $error = false)
    {
        $this->_notifications[] = $this->_setNotificationErrorStyle($message, $error);

        return $this;
    }

    /**
     *
     * @return \Zitec_Dpd_Model_Dpd_Manifest
     */
    protected function _clearNotificationErrorStyles()
    {
        foreach ($this->_notifications as $index => $notification) {
            $this->_notifications[$index] = $this->_setNotificationErrorStyle($notification, false);
        }

        return $this;
    }

    /**
     *
     * @param string  $message
     * @param boolean $on
     *
     * @return string
     */
    protected function _setNotificationErrorStyle($message, $on)
    {
        $openTag  = '<span class="error-msg">';
        $closeTag = '</span>';
        $message  = str_replace($openTag, '', $message);
        $message  = str_replace($closeTag, '', $message);
        if ($on) {
            $message = $openTag . $message . $closeTag;
        }

        return $message;
    }

    /**
     *
     * @param array $shipsForManifest
     *
     * @return \Zitec_Dpd_Model_Dpd_Manifest
     */
    public function setShipsForManifest(array $shipsForManifest)
    {
        $this->_shipsForManifest = $shipsForManifest;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getShipsForManifest()
    {
        return $this->_shipsForManifest;
    }

    /**
     *
     * @return array
     */
    public function getNotifications()
    {
        return $this->_notifications;
    }
}
