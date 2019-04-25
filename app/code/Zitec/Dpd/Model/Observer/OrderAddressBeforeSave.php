<?php

namespace Zitec\Dpd\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderAddressBeforeSave implements ObserverInterface
{
    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;

    public function __construct(
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->dpdHelper = $dpdHelper;
    }

    /**
     * change the status of postcode validation
     *
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

        $origPostcode = $address->getOrigData('postcode');
        $newPostcode  = $address->getPostcode();
        if ($origPostcode != $newPostcode){
            $address->setValidAutoPostcode(1);
        }


    }
}
