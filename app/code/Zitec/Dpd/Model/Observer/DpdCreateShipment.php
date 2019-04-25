<?php

namespace Zitec\Dpd\Model\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;

class DpdCreateShipment extends Shipment
{
    /**
     * Saves the DPD shipment when the shipment is created.
     *
     * @param void
     *
     * @return \Magento\Framework\DataObject
     */
    public function createShipment($request)
    {
        /**
         * @var $shipment \Magento\Sales\Model\Order\Shipment
         */
        $shipment = $request->getOrderShipment();
        if (!$this->_canSaveDpdShipment($shipment)) {
            return;
        }

        //$this->_isProcessed = true;

        $this->_setShipment($shipment);
        $this->_setOrder($this->_getShipment()->getOrder());

        $packages = $shipment->getPackages();
        if (empty($packages)) {
            return;
        }

        /** @var \Zitec_Dpd_Api_Shipment_Save_Response $createShippingResponse */
        $createShippingResponse = $this->_createShipment($packages);

        $shippingLabel = $this->_getLabels();


        $successNotice = __('Your new shipment was successfully communicated to DPD.');
        if ($this->_getDPDMessage()) {
            $successNotice .= '<br />' . sprintf(__('DPD says, "%1"'), $this->_getDPDMessage());
        }
        $this->dpdHelper->addNotice($successNotice);

        return new \Magento\Framework\DataObject([
            'tracking_number' => $createShippingResponse->getDpdShipmentId(),
            'shipping_label_content' => $shippingLabel,
        ]);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return bool
     */
    protected function _canSaveDpdShipment(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        if (!$this->dpdHelper->moduleIsActive($shipment->getOrder()->getStore())) {
            return false;
        }

        if (!$this->dpdHelper->isShippingMethodDpd($shipment->getOrder()->getShippingMethod())) {
            return false;
        }

        return true;
    }

    /**
     * @param array $packages
     *
     * @return \Zitec_Dpd_Api_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _createShipment($packages)
    {
        /** @var \Zitec_Dpd_Api $dpdApi */
        $dpdApi = new \Zitec_Dpd_Api($this->_getShipmentParams());

        /** @var \Zitec_Dpd_Api_Shipment_Save $dpdShipment */
        $dpdShipment = $dpdApi->getApiMethodObject();

        $serviceCode = $this->dpdHelper->getDPDServiceCode($this->_getOrder()->getShippingMethod());
        if (!$serviceCode) {
            throw new LocalizedException(sprintf(__("An error occurred communicating the shipment to DPD. The shipping method '%1' is invalid"),
                $this->_getOrder()->getShippingMethod()));
        }

        $address = $this->_getShippingAddress();
        $dpdShipment->setReceiverAddress($address)
            ->setShipmentReferenceNumber(uniqid('', false))
            ->setShipmentServiceCode($serviceCode);



        $predict_methods = $this->dpdHelper->getConfigData('services_predict');
        $predict_methods_by_keys = array_flip(explode(',', $predict_methods));

        if ($address->getTelephone() !== '' && array_key_exists($serviceCode, $predict_methods_by_keys)) {
            $dpdShipment->setPredictPhoneNumber($address['telephone']);
        }

        foreach ($packages as $packageIdx => $package) {
            $dpdShipment->addParcel($packageIdx, $package['params']['weight'], $this->getPackageDescription($package));
        }

        if ($this->dpdHelper->isOrderCashOnDelivery($this->_getOrder())) {
            $paymentType = $this->dpdHelper->getCodPaymentType($this->_getOrder());
            $dpdShipment->setCashOnDelivery(round($this->_getOrder()->getBaseGrandTotal(), 2),
                $this->_getOrder()->getBaseCurrencyCode(), $paymentType);
        }
        $order = $this->_getOrder();
        $insurance = $this->dpdHelper->extractInsuranceValuesByOrder($order);

        $shouldSendInsuranceValue = $this->dpdHelper->getConfigData('send_insurance_value');
        if ($shouldSendInsuranceValue) {
            $dpdShipment->setAdditionalHighInsurance($insurance['goodsValue'], $insurance['currency'], $insurance['content']);
        }

        try {
            $response = $dpdShipment->execute();
        } catch (\Zitec_Dpd_Api_Shipment_Save_Exception_ReceiverAddressTooLong $e) {
            throw new LocalizedException(__("The shipment could not be communicated to DPD because the shipping street the maximum permitted length of %1 characters. <br />Please edit the shipping address to reduce the length of the street in the shipping address.", $e->getMaxLength()));
        } catch (\Exception $e) {
            throw new LocalizedException(__("An error occurred communicating the shipment to DPD at %1:<br /> '%2'", $dpdShipment->getUrl(), $e->getMessage()));
        }

        if ($response->hasError()) {
            throw new LocalizedException(__('DPD could not process the new shipment. The following error was returned: <br /> "%1: %2"', $response->getErrorCode(), $response->getErrorText()));
        }

        $this->_response = $response;
        $this->_call = $dpdShipment;

        $this->_saveShipmentResponse($response, $dpdShipment);

        return $response;
    }

    /**
     *
     * @return boolean
     */
    protected function _getLabels()
    {
        try {
            $this->_labelPdfStr = $this->dpdWsHelper->getNewPdfShipmentLabelsStr($this->_response->getDpdShipmentId(),
                $this->_response->getDpdShipmentReferenceNumber());
            //$this->_getShipment()->setShippingLabel($this->_labelPdfStr)->save();

        } catch (\Exception $e) {
            throw new LocalizedException(sprintf('An error occurred whilst retreiving the shipping labels from DPD for the new shipment. <br /> "%1"'));
        }

        return $this->_labelPdfStr;
    }

    /**
     *
     * @param \Zitec_Dpd_Api_Shipment_Save_Response $response
     *
     * @return boolean
     */
    protected function _saveShipmentResponse($response, $call)
    {
        $ship = $this->dpdDpdShipFactory->create();
        $ship->setShipmentId($this->_getShipment()->getId())
            ->setOrderId($this->_getOrder()->getId())
            ->setSaveShipmentCall(serialize($call))
            ->setShippingLabels(base64_encode($this->_labelPdfStr))
            ->setSaveShipmentResponse(serialize($response))
            ->save();

        //$this->_saveTracking($response);

        //$this->_getShipment()->setShippingLabel($this->_labelPdfStr)->save();

        return true;
    }

    /**
     * @param \Zitec_Dpd_Api_Shipment_Save_Response $response
     *
     * @return bool
     * @throws \Exception
     */
    protected function _saveTracking($response)
    {
        $trackNumber = $response->getDpdShipmentReferenceNumber();
        $carrier = $this->dpdHelper->getDpdCarrierCode();
        $shipment = $this->_getShipment();
        $carrierName = $this->dpdHelper->getCarrierName($this->_getOrder()->getStore());
        $track = $this->salesOrderShipmentTrackFactory->create()
            ->setNumber($trackNumber)
            ->setTrackNumber($trackNumber)
            ->setCarrierCode($carrier)
            ->setTitle($carrierName);
        $shipment->addTrack($track);
        $shipment->save();

        return true;
    }

    /**
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    protected function _getShipment()
    {
        return $this->_shipment;
    }

    /**
     * Important: Here we load the shipping address from the database rather than
     * using the one accessible from the order. This is intentional. using the one on the order
     * appears to cause a crash with some versions of PHP.
     *
     * @return \Magento\Sales\Model\Order\Address
     */
    protected function _getShippingAddress()
    {
        if (!$this->_shippingAddress) {
            $this->_shippingAddress = $this->salesOrderAddressFactory->create()->load($this->_getOrder()
                ->getShippingAddressId());
        }

        return $this->_shippingAddress;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return \Zitec_Dpd_Model_Observer_Shipment
     */
    protected function _setShipment(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $this->_shipment = $shipment;

        return $this;
    }

    /**
     *
     * @return array
     */
    protected function _getShipmentParams()
    {
        $apiParams           = $this->dpdWsHelper->getShipmentParams($this->_getOrder()->getStoreId());
        $apiParams['method'] = \Zitec_Dpd_Api_Configs::METHOD_CREATE_SHIPMENT;

        return $apiParams;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return \Zitec_Dpd_Model_Observer_Shipment
     */
    protected function _setOrder(\Magento\Sales\Model\Order $order)
    {
        $this->_order = $order;

        return $this;
    }

    /**
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        return $this->_order;
    }

    /**
     *
     * @return string|boolean
     */
    protected function _getDPDMessage()
    {
        if ($this->_response instanceof \Zitec_Dpd_Api_Shipment_Save_Response) {
            return $this->_response->getMessage();
        } else {
            return false;
        }
    }

    private function getPackageDescription($package)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $productLabels = [];
        foreach ($package['items'] as $productId=>$item) {
            $product = $objectManager->create(\Magento\Catalog\Model\Product::class)->load($item['product_id']);
            $productLabels[] = ($item['qty'] . '_' . $product->getSku() . '_' . $product->getName());
        }

        $packageReference = implode(' ', $productLabels);

        return $packageReference;
    }
}
