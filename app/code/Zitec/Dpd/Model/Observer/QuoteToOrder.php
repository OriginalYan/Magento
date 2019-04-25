<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 3/6/2017
 * Time: 11:07 PM
 */

namespace Zitec\Dpd\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteToOrder implements ObserverInterface
{

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $address = $observer->getQuote()->getShippingAddress();
        $order = $observer->getOrder();

        $keys = [
            'zitec_dpd_cashondelivery_surcharge',
            'base_zitec_dpd_cashondelivery_surcharge',
            'zitec_dpd_cashondelivery_surcharge_tax',
            'base_zitec_dpd_cashondelivery_surcharge_tax',
        ];

        foreach ($keys as $key) {
            $order->setData($key, $address->getData($key));
        }
    }
}
