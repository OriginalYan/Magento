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
class Tax extends \Magento\Sales\Model\Order\Creditmemo\Total\Tax
{
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {

        $result = parent::collect($creditmemo);

        // We added taxes on delivery surcharge
        $creditmemo->setData("zitec_dpd_cashondelivery_surcharge_tax", 0);
        $creditmemo->setData("base_zitec_dpd_cashondelivery_surcharge_tax", 0);
        if ($creditmemo->getData("zitec_dpd_cashondelivery_surcharge")) {
            $baseTax = $creditmemo->getOrder()->getData("base_zitec_dpd_cashondelivery_surcharge_tax");
            $creditmemo->setData("base_zitec_dpd_cashondelivery_surcharge_tax", $baseTax);
            $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseTax);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTax);

            $tax = $creditmemo->getOrder()->getData("zitec_dpd_cashondelivery_surcharge_tax");
            $creditmemo->setData("zitec_dpd_cashondelivery_surcharge_tax", $tax);
            $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $tax);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $tax);
        }

        return $result;
    }
}


