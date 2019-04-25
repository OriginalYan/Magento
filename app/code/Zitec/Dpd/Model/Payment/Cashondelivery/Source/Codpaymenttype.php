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

namespace Zitec\Dpd\Model\Payment\Cashondelivery\Source;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Codpaymenttype implements\Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $codPaymentTypes = [
            \Zitec_Dpd_Api_Configs::PAYMENT_TYPE_CASH          => __('Cash'),
            \Zitec_Dpd_Api_Configs::PAYMENT_TYPE_CREDIT_CARD   => __('Credit Card'),
            \Zitec_Dpd_Api_Configs::PAYMENT_TYPE_CROSSED_CHECK => __('Crossed Check')
        ];

        $options = [];
        foreach ($codPaymentTypes as $type=>$codPaymentType) {
            $options[] = ['label' => $codPaymentType, 'value' => $type];
        }

        return $options;
    }
}
