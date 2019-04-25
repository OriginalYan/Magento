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

namespace Zitec\Dpd\Controller\Adminhtml;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class ValidatePostcode extends \Magento\Backend\App\Action
{

    /**
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    protected $dpdPostcodeSearchHelper;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $salesOrderShipmentFactory;

    /**
     * @var \Zitec\Dpd\Model\Dpd\ManifestFactory
     */
    protected $dpdDpdManifestFactory;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory
     */
    protected $dpdMysql4DpdShipCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $salesResourceModelOrderShipmentCollectionFactory;

    /**
     * @var \Zitec\Dpd\Model\Dpd\PickupFactory
     */
    protected $dpdDpdPickupFactory;

    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    protected $dpdWsHelper;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Zitec\Dpd\Helper\ApiFactory
     */
    protected $dpdApiFactory;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Zitec\Dpd\Helper\Postcode\Search $dpdPostcodeSearchHelper,
        \Magento\Sales\Model\Order\ShipmentFactory $salesOrderShipmentFactory,
        \Zitec\Dpd\Model\Dpd\ManifestFactory $dpdDpdManifestFactory,
        \Zitec\Dpd\Model\Mysql4\Dpd\Ship\CollectionFactory $dpdMysql4DpdShipCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $salesResourceModelOrderShipmentCollectionFactory,
        \Zitec\Dpd\Model\Dpd\PickupFactory $dpdDpdPickupFactory,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Helper\ApiFactory $dpdApiFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        parent::__construct($context);

        $this->dpdApiFactory = $dpdApiFactory;
        $this->dpdPostcodeSearchHelper = $dpdPostcodeSearchHelper;
        $this->salesOrderShipmentFactory = $salesOrderShipmentFactory;
        $this->dpdDpdManifestFactory = $dpdDpdManifestFactory;
        $this->dpdMysql4DpdShipCollectionFactory = $dpdMysql4DpdShipCollectionFactory;
        $this->salesResourceModelOrderShipmentCollectionFactory = $salesResourceModelOrderShipmentCollectionFactory;
        $this->dpdDpdPickupFactory = $dpdDpdPickupFactory;
        $this->dpdWsHelper = $dpdWsHelper;
        $this->dpdHelper = $dpdHelper;
        $this->jsonHelper = $jsonHelper;
    }

    public function execute()
    {
        // TODO: Implement execute() method.
    }

    /**
     * this action is used to validate manually the address postcode
     */
    public function validatePostcodeAction(){
        $params = $this->getRequest()->getParams();
        $address = '';
        foreach($params['street'] as $street){
            $address .=  ' '.$street;
        }
        $address = trim($address);
        $params['address'] = $address;
        $foundAddresses = $this->dpdPostcodeSearchHelper->findAllSimilarAddressesForAddress($params);
        $content = $this->getLayout()
            ->createBlock('zitec_dpd/adminhtml_shipment_postcode_autocompleter')
            ->setData('found_addresses',$foundAddresses)
            ->setTemplate('zitec_dpd/sales/order/shipment/postcode/autocompleter.phtml')->toHtml();

        $this->getResponse()->setBody($content);

    }


    /**
     * download the pdf containg labels for each parcel
     *
     * @return \Magento\Framework\App\Action\Action
     */
    public function getLabelPdfAction()
    {
        $shipmentId = $this->getRequest()->getParam('shipmentid');
        if (!$shipmentId) {

        }
        $shipment = $this->salesOrderShipmentFactory->create()->load($shipmentId);
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipmentLabel = $shipment->getShippingLabel();
        $pdf = \Zend_Pdf::parse($shipmentLabel);

        return $this->_prepareDownloadResponse($shipment->getIncrementId().'_dpd_'.$shipment->getCreatedAt().'.pdf', $pdf->render(), 'application/pdf');
    }

    /**
     * merge more labels into on pdf and return the Zend_Pdf object
     *
     * @param array $labelsContent
     *
     * @return \Zend_Pdf
     */
    protected function _combineLabelsPdf(array $labelsContent)
    {
        $outputPdf = new \Zend_Pdf();
        foreach ($labelsContent as $content) {
            if (stripos($content, '%PDF-') !== false) {
                $pdfLabel = \Zend_Pdf::parse($content);
                foreach ($pdfLabel->pages as $page) {
                    $outputPdf->pages[] = clone $page;
                }
            } else {
                $page = $this->_createPdfPageFromImageString($content);
                if ($page) {
                    $outputPdf->pages[] = $page;
                }
            }
        }

        return $outputPdf;
    }


    protected function _createPdfPageFromImageString($imageString)
    {
        $image = imagecreatefromstring($imageString);
        if (!$image) {
            return false;
        }

        $xSize = imagesx($image);
        $ySize = imagesy($image);
        $page  = new \Zend_Pdf_Page($xSize, $ySize);

        imageinterlace($image, 0);
        $tmpFileName = sys_get_temp_dir() . DS . 'shipping_labels_'
            . uniqid(mt_rand()) . time() . '.png';
        imagepng($image, $tmpFileName);
        $pdfImage = \Zend_Pdf_Image::imageWithPath($tmpFileName);
        $page->drawImage($pdfImage, 0, 0, $xSize, $ySize);
        unlink($tmpFileName);

        return $page;
    }

    /**
     * Delete shipment if the manifest was not closed before
     */
    public function deleteAction()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        if (!$shipmentId) {
            $this->_setDeleteResponse("No shipment was specified", $shipmentId);

            return;
        }
        $shipment = $this->salesOrderShipmentFactory->create();
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment->load($shipmentId);

        $ships = $this->dpdMysql4DpdShipCollectionFactory->create();
        /* @var $ships Zitec_Dpd_Model_Mysql4_Dpd_Ship_Collection */
        $ship = $ships->getByShipmentId($shipmentId);
        /*  @var  $ship Zitec_Dpd_Model_Dpd_Ship */
        if (!$ship) {
            $this->_setDeleteResponse("Could not find any DPD shipment information for this shipment.", $shipment);

            return;
        }

        if ($ship->getManifestId()) {
            $this->_setDeleteResponse("You cannot cancel this shipment with DPD because the manifest is already closed.", $shipment);

            return;
        }
        $response = @unserialize($ship->getSaveShipmentResponse());
        /* @var $response Zitec_Dpd_Api_Shipment_Save_Response */
        if (!$response) {
            $this->_setDeleteResponse("Unable to load shipment information for this shipment.", $shipment);

            return;
        }


        try {
            $wsResult = $this->dpdWsHelper->deleteWsShipment($shipment,$response);
        } catch (\Exception $e) {
            $this->_setDeleteResponse('An error occurred whilst attempting to delete the shipment information with DPD. <br /> "%1"', $shipment, $e->getMessage());

            return;
        }

        if ($wsResult->hasError()) {
            $this->_setDeleteResponse('An error occurred whilst attempting to delete the shipment information with DPD. <br /> "%1"', $shipment, $wsResult->getErrorText());

            return;
        }

        $shipment->setShippingLabel(null)->save();
        $ship->setShippingLabels(null)->save();

        $this->_forward("removeTrack", "sales_order_shipment", "admin");
    }



    protected function _setDeleteResponse($message, $shipment, $additional = '', $isError = true)
    {
        $response = array(
            'error'   => $isError,
            'message' => __($message, $additional),
        );
        $response = $this->jsonHelper->jsonEncode($response);
        $this->getResponse()->setBody($response);
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


    /**
     *
     * Create a pickup request in the future
     * sender address have to be configures
     * shipment should be already generated
     * the manifest can be closed or not
     */
    public function createPickupAction()
    {
        $shipmentIds = $this->getRequest()->getParam("shipment_ids");
        if (!$shipmentIds) {
            $this->_createPickupRedirect(__('Please select the shipments for which you wish to arrange a pickup.'));

            return;
        }

        list($day, $month, $year) = explode("/", $this->getRequest()->getParam("zitec_dpd_pickup_date"));
        if (!checkdate($month, $day, $year)) {
            $this->_createPickupRedirect(__('Please enter a pickup date in the format DD/MM/YYYY.'));

            return;
        }
        $year       = isset($year) && strlen($year) == 2 ? "20$year" : $year;
        $month      = isset($month) && strlen($month) < 2 ? str_pad($month, 2, "0") : $month;
        $day        = isset($day) && strlen($day) < 2 ? str_pad($day, 2, "0") : $day;
        $pickupDate = "$year$month$day";

        $pickupFromParts = $this->getRequest()->getParam("zitec_dpd_pickup_from");
        if (!is_array($pickupFromParts) || count($pickupFromParts) != 3) {
            $this->_createPickupRedirect(__('Please select a from and to time for the pickup.'));

            return;
        }
        $pickupFrom = implode("", $pickupFromParts);

        $pickupToParts = $this->getRequest()->getParam("zitec_dpd_pickup_to");
        if (!is_array($pickupToParts) || count($pickupToParts) != 3) {
            $this->dpdHelper->addError(__('Please select a from and to time for the pickup.'));
            $this->_redirect("adminhtml/sales_shipment/index");

            return;
        }
        $pickupTo = implode("", $pickupToParts);

        $instruction = $this->getRequest()->getParam("zitec_dpd_pickup_instruction");

        $pickupAddress = $this->dpdWsHelper->getPickupAddress();
        if (!is_array($pickupAddress)) {
            $this->dpdHelper->addError(__('You cannot create a pickup because you have not fully specified your pickup address. <br />Please set your pickup address in System->Configuration->Sales->Shipping Settings->DPD GeoPost Pickup Address.'));
            $this->_redirect("adminhtml/sales_shipment/index");

            return;
        }

        $apiParams = $this->dpdWsHelper->getPickupParams();
        $apiParams['method'] = \Zitec_Dpd_Api_Configs::METHOD_PICKUP_CREATE;

        $dpdApi = $this->dpdApiFactory->create($apiParams);
        $createPickup = $dpdApi->getApiMethodObject();

        $createPickup->setPickupTime($pickupDate, $pickupFrom, $pickupTo);
        $createPickup->setSpecialInstruction($instruction);
        $createPickup->setPickupAddress($pickupAddress);

        $shipments = $this->salesResourceModelOrderShipmentCollectionFactory->create();
        /* @var $shipments Mage_Sales_Model_Resource_Order_Shipment_Collection */
        $shipments->addFieldToFilter('entity_id', array("in" => $shipmentIds));

        $ships = $this->dpdMysql4DpdShipCollectionFactory->create();
        /* @var $ships Zitec_Dpd_Model_Mysql4_Dpd_Ship_Collection */
        $ships->filterByShipmentIds($shipmentIds);


        $includedShipments = array();
        foreach ($shipments as $shipment) {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            $ship = $ships->findByShipmentId($shipment->getId());
            /* @var $ship Zitec_Dpd_Model_Dpd_Ship */
            if (!$ship || !$this->dpdHelper->isShippingMethodDpd($shipment) || $this->dpdHelper->isCancelledWithDpd($shipment)) {
                continue;
            }
            $includedShipments[] = $shipment;

            $call = @unserialize($ship->getSaveShipmentCall());
            /* @var $call Zitec_Dpd_Api_Shipment_Save */
            if (!$call) {
                $message = __("Unable to load shipment information for this shipment %1.", $shipment);
                $this->_createPickupRedirect($message);

                return;
            }
            $createPickup->addPieces($call->getShipmentServiceCode(), $call->getParcelCount(), $call->getTotalWeight(), $call->getReceiverCountryCode());
        }
        if (!$includedShipments) {
            $message = __("Your list did not contain any DPD shipments for which to arrange a pickup.", $shipment);
            $this->_createPickupRedirect($message);

            return;
        }

        try {
            $createPickup->execute();
        } catch (\Exception $e) {
            $message = __('A problem occurred whilst communicating your shipment to DPD. <br />"%1"', $e->getMessage());
            $this->dpdHelper->log($message);
            $this->_createPickupRedirect($message);

            return;
        }
        $response = $createPickup->getCreatePickupResponse();
        if ($response->hasError()) {
            $message = __('DPD reported an error whilst attempting to arrange your pickup. <br />DPD says, "%1"', $response->getErrorText());
            $this->dpdHelper->log($message);
            $this->_createPickupRedirect($message);

            return;
        }

        $pickup = $this->dpdDpdPickupFactory->create();
        /* @var $pickup Zitec_Dpd_Model_Dpd_Pickup */

        $pickup->setReference($response->getReferenceNumber())
            ->setDpdId($response->getDpdId())
            ->setPickupDate("$year-$month-$day")
            ->setPickupTimeFrom("$year-$month-$day " . implode(":", $pickupFromParts))
            ->setPickupTimeTo("$year-$month-$day " . implode(":", $pickupToParts))
            ->setCallData(serialize($createPickup))
            ->setResponseData(serialize($response))
            ->save();

        foreach ($includedShipments as $includedShipment) {
            $includedShipment->setData('zitec_dpd_pickup_time', "$year-$month-$day " . implode(":", $pickupFromParts));
            $includedShipment->setData('zitec_dpd_pickup_id', $pickup->getEntityId());
            $includedShipment->save();
        }

        $this->dpdHelper->addNotice("Your pickup was created successfully");
        $this->_redirect("adminhtml/sales_shipment/index");
    }

    /**
     *
     * @param string  $message
     * @param boolean $isError
     */
    protected function _createPickupRedirect($message, $isError = true)
    {
        if ($isError) {
            $this->dpdHelper->addError($message);
        } else {
            $this->dpdHelper->addNotice($message);
        }
        $this->_redirect("adminhtml/sales_shipment/index");
    }
}
