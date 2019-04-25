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

namespace Zitec\Dpd\Model\Sales\Order\Creditmemo\Total;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Cashondeliverysurchage extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{

    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {


        $creditmemo->setData('zitec_dpd_cashondelivery_surcharge', 0);
        $creditmemo->setData('base_zitec_dpd_cashondelivery_surcharge', 0);

        $order = $creditmemo->getOrder();


        //Check creditmemo before to see if we have already returned
        $amount = $order->getData('zitec_dpd_cashondelivery_surcharge');
        if ($amount) {
            foreach ($order->getCreditmemosCollection() as $previousCreditMemo) {
                /* @var $previousCreditMemo Mage_Sales_Model_Order_Creditmemo */
                if ($previousCreditMemo->getData("base_zitec_dpd_cashondelivery_surcharge")) {
                    return $this;
                }
            }

            // NB. Here we add one to the total tax base. Taxes
            // are added in Zitec_Dpd_Model_Sales_Order_Creditmemo_Total_Tax
            $creditmemo->setData('zitec_dpd_cashondelivery_surcharge', $amount);

            $baseAmount = $order->getData('base_zitec_dpd_cashondelivery_surcharge');
            $creditmemo->setData('base_zitec_dpd_cashondelivery_surcharge', $baseAmount);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);

        }

        return $this;
    }

}