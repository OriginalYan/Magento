<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 3/12/2017
 * Time: 5:12 PM
 */

namespace Zitec\Dpd\Plugin;


use Magento\Shipping\Controller\Adminhtml\Order\Shipment\RemoveTrack;

class ShipmentRemoveTrack
{

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory
     */
    private $dpdMysql4DpdShipCollectionFactory;

    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    private $dpdWsHelper;
    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    private $action;

    public function __construct(
        \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory $dpdMysql4DpdShipCollectionFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->dpdMysql4DpdShipCollectionFactory = $dpdMysql4DpdShipCollectionFactory;
        $this->dpdWsHelper = $dpdWsHelper;
        $this->dpdHelper = $dpdHelper;
        $this->jsonHelper = $jsonHelper;
        $this->shipmentRepository = $shipmentRepository;
    }

    public function aroundExecute(
        RemoveTrack $action,
        \Closure $proceed
    ) {
        $this->action = $action;

        $shipmentId = $action->getRequest()->getParam('shipment_id');

        $shipment = $this->shipmentRepository->get($shipmentId);

        if (!$shipment) {
            $this->_setDeleteResponse(__('We can\'t initialize shipment for delete tracking number.'), $shipment);
        }

        $shipCollection = $this->dpdMysql4DpdShipCollectionFactory->create();
        $ship = $shipCollection->getByShipmentId($shipmentId);

        if (!$ship) {
            $this->_setDeleteResponse("Could not find any DPD shipment information for this shipment.", $shipment);

            return;
        }

        if ($ship->getManifestId()) {
            $this->_setDeleteResponse("You cannot cancel this shipment with DPD because the manifest is already closed.", $shipment);

            return;
        }

        $response = @unserialize($ship->getSaveShipmentResponse());
        /* @var $response \Zitec_Dpd_Api_Shipment_Save_Response */
        if (!$response) {
            $this->_setDeleteResponse("Unable to load shipment information for this shipment.", $shipment);

            return;
        }


        try {
            $wsResult = $this->dpdWsHelper->deleteWsShipment($shipment, $response);
        } catch (\Exception $e) {
            $this->_setDeleteResponse('An error occurred whilst attempting to delete the shipment information with DPD. <br /> "%1"', $shipment, $e->getMessage());

            return;
        }

        if ($wsResult->hasError()) {
            $this->_setDeleteResponse('An error occurred whilst attempting to delete the shipment information with DPD. <br /> "%1"', $shipment, $wsResult->getErrorText());

            return;
        }

        $shipment->setShippingLabel(null)->save();
        $ship->delete();

        //call original execute()
        $proceed();
    }

    protected function _setDeleteResponse($message, $shipment, $additional = '', $isError = true)
    {
        $response = array(
            'error'   => $isError,
            'message' => PHP_EOL . __($message, $additional),
        );

        $response = $this->jsonHelper->jsonEncode($response);
        $this->action->getResponse()->setBody($response);
        if ($isError) {
            $isShipmentLoaded = $shipment instanceof \Magento\Sales\Model\Order\Shipment;
            $incrementId      = $isShipmentLoaded ? $shipment->getIncrementId() : "Unknown";
            $shipmentId       = $isShipmentLoaded ? $shipment->getId() : $shipment;
            $this->dpdHelper->log(sprintf("Error deleting shipment, id: %1, reference: %1", $shipmentId, $incrementId));
            $this->dpdHelper->log(sprintf("Message: %1", $message));
            if ($additional) {
                $this->dpdHelper->log(sprintf("Additional: %1", $additional));
            }
        }

        return $isError;
    }
}
