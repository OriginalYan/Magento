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

namespace Zitec\Dpd\Model\Sales\Order\Total;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Cashondeliverysurchage extends \Magento\Sales\Model\Order\Total\AbstractTotal
{
    public function collect(\Magento\Sales\Model\Order $order)
    {
        $order->setData('zitec_dpd_cashondelivery_surcharge', 0);
        $order->setData('base_zitec_dpd_cashondelivery_surcharge', 0);

        $amount = $order->getOrder()->getData('zitec_dpd_cashondelivery_surcharge');
        $order->setData('zitec_dpd_cashondelivery_surcharge', $amount);

        $amount = $order->getOrder()->getData('base_zitec_dpd_cashondelivery_surcharge');
        $order->setData('base_zitec_dpd_cashondelivery_surcharge', $amount);

        $order->setGrandTotal($order->getGrandTotal() + $order->getData('zitec_dpd_cashondelivery_surcharge'));
        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $order->getData('base_zitec_dpd_cashondelivery_surcharge'));

        return $this;
    }
}