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

namespace Zitec\Dpd\Helper;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Layout extends \Magento\Framework\App\Helper\AbstractHelper
{

    const DEFAULT_TRACKING_TEMPLATE = 'sales/order/shipment/view/tracking.phtml';
    const DEFAULT_SHIPPING_TRACKING_POPUP_TEMPLATE = "shipping/tracking/popup.phtml";

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $salesOrderShipmentFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $salesOrderShipmentTrackFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\Order\ShipmentFactory $salesOrderShipmentFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $salesOrderShipmentTrackFactory,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->salesOrderShipmentFactory = $salesOrderShipmentFactory;
        $this->registry = $registry;
        $this->salesOrderShipmentTrackFactory = $salesOrderShipmentTrackFactory;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->dpdHelper = $dpdHelper;
        parent::__construct(
            $context
        );
    }

    /**
     *
     * @return string
     */
    public function changeShippingTrackingPopupTemplate()
    {
        if (!$this->dpdHelper->moduleIsActive()) {
            return self::DEFAULT_SHIPPING_TRACKING_POPUP_TEMPLATE;
        }

        $shippingInfo = $this->registry->registry("current_shipping_info");
        /* @var $shippingInfo Mage_Shipping_Model_Info */
        if (!$shippingInfo instanceof \Magento\Shipping\Model\Info) {
            return self::DEFAULT_SHIPPING_TRACKING_POPUP_TEMPLATE;
        }


        if ($shippingInfo->getOrderId()) {
            $orderId = $shippingInfo->getOrderId();
        } elseif ($shippingInfo->getShipId()) {
            $orderId = $this->salesOrderShipmentFactory->create()->load($shippingInfo->getShipId())->getOrderId();
        } elseif ($shippingInfo->getTrackId()) {
            $orderId = $this->salesOrderShipmentTrackFactory->create()->load($shippingInfo->getTrackId())->getOrderId();
        } else {
            return self::DEFAULT_SHIPPING_TRACKING_POPUP_TEMPLATE;
        }

        if (!$orderId) {
            return self::DEFAULT_SHIPPING_TRACKING_POPUP_TEMPLATE;
        }

        $shippingMethod = $this->salesOrderFactory->create()->load($orderId)->getShippingMethod();
        if (!$this->dpdHelper->isShippingMethodDpd($shippingMethod)) {
            return self::DEFAULT_SHIPPING_TRACKING_POPUP_TEMPLATE;
        }

        return 'zitec_dpd/shipping/tracking/popup.phtml';
    }
}
