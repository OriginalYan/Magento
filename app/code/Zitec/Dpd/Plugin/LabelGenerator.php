<?php

namespace Zitec\Dpd\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;
use Zitec\Dpd\Model\Shipping\Carrier\Dpd;

class LabelGenerator extends \Magento\Shipping\Model\Shipping\LabelGenerator
{

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(Shipment $shipment, RequestInterface $request)
    {
        $order = $shipment->getOrder();
        $carrier = $this->carrierFactory->create($order->getShippingMethod(true)->getCarrierCode());
        if (!$carrier->isShippingLabelsAvailable()) {
            throw new LocalizedException(__('Shipping labels is not available.'));
        }
        $shipment->setPackages($request->getParam('packages'));
        $response = $this->labelFactory->create()->requestToShipment($shipment);
        if ($response->hasErrors()) {
            throw new LocalizedException(__($response->getErrors()));
        }
        if (!$response->hasInfo()) {
            throw new LocalizedException(__('Response info is not exist.'));
        }
        $labelsContent = [];
        $trackingNumbers = [];
        $info = $response->getInfo();
        foreach ($info as $inf) {
            if (!empty($inf['tracking_number']) && !empty($inf['label_content'])) {
                $labelsContent[] = $inf['label_content'];
                $trackingNumbers[] = $inf['tracking_number'];
            }
        }

        //DPD sends labels as PDF 1.7, which is not supported by Magento 2 (which is using Zend_Pdf_Parser)
        if (Dpd::CARRIER_CODE === $shipment->getOrder()->getShippingMethod(true)->getCarrierCode()) {
            $pdf = reset($labelsContent);
        } else {
            $outputPdf = $this->combineLabelsPdf($labelsContent);
            $pdf = $outputPdf->render();
        }

        $shipment->setShippingLabel($pdf);
        $carrierCode = $carrier->getCarrierCode();
        $carrierTitle = $this->scopeConfig->getValue(
          'carriers/' . $carrierCode . '/title',
          ScopeInterface::SCOPE_STORE,
          $shipment->getStoreId()
        );
        if (!empty($trackingNumbers)) {
            $this->addTrackingNumbersToShipment($shipment, $trackingNumbers, $carrierCode, $carrierTitle);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param array $trackingNumbers
     * @param string $carrierCode
     * @param string $carrierTitle
     *
     * @return void
     */
    private function addTrackingNumbersToShipment(
      Shipment $shipment,
      $trackingNumbers,
      $carrierCode,
      $carrierTitle
    ) {
        foreach ($trackingNumbers as $number) {
            if (is_array($number)) {
                $this->addTrackingNumbersToShipment($shipment, $number, $carrierCode, $carrierTitle);
            } else {
                $shipment->addTrack(
                  $this->trackFactory->create()
                    ->setNumber($number)
                    ->setCarrierCode($carrierCode)
                    ->setTitle($carrierTitle)
                );
            }
        }
    }
}
