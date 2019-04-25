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
class AmountType implements\Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $amountTypes = array(
            \Zitec_Dpd_Api_Configs::PAYMENT_AMOUNT_TYPE_FIXED      => __('Fixed amount'),
            \Zitec_Dpd_Api_Configs::PAYMENT_AMOUNT_TYPE_PERCENTAGE => __('Percentage of entire order'),
        );

        $options = [];
        foreach ($amountTypes as $type=>$amountType) {
            $options[] = ['label' => $amountType, 'value' => $type];
        }

        return $options;
    }
}


