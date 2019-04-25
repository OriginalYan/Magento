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

namespace Zitec\Dpd\Model\Config\Source;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Service
{
    protected $_services = [];

    public function __construct()
    {
        $this->_services = [
            '1'     => __('DPD Classic'),
            '2303' => __('DPD Classic International'),
            '2202' => __('DPD Classic Bulgaria'),
            '2204' => __('DPD Classic Ungaria'),
            '2205' => __('DPD Classic Grecia'),
        ];
    }

    public function toOptionArray($isMultiselect = false)
    {

        $options = [];
        foreach ($this->_services as $serviceCode => $label) {
            $options[] = ['label' => $serviceCode . ' - ' . $label, 'value' => $serviceCode];
        }

        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }

    /**
     * return available services
     */
    public function getAvailableServices()
    {
        return $this->_services;
    }

    /**
     * return default values for all shipping service of dpd carrier to force using
     * api response amount for enabled services
     *
     * @return array
     */
    public function getDefaultShippingRates()
    {
        $rates = [];
        foreach ($this->getAvailableServices() as $key => $value) {
            $rates[] = [
                //'pk'=>14,
                'website_id'               => null,//1
                'dest_country_id'          => 0,
                'dest_region_id'           => 0,
                'dest_zip'                 => '',
                'weight'                   => 0,
                'price'                    => 0,
                'method'                   => $key,
                'markup_type'              => 1,
                'cod_option'               => 3,
                'cashondelivery_surcharge' => "0%",
                'cod_min_surcharge'        => null,
                'price_vs_dest'            => 0,
            ];
        }

        return $rates;

    }


    /**
     * return the payment rate with default values
     * it will return an array forcing the payment to be calculated as a percentage
     *
     * @return array
     */
    public function getDefaultPaymentRate()
    {
        $rate = [
            'website_id'               => null,
            'dest_country_id'          => 0,
            'dest_region_id'           => 0,
            'dest_zip'                 => '',
            'weight'                   => 0,
            'price'                    => 0,
            'markup_type'              => 1,
            'cod_option'               => 3,
            'cashondelivery_surcharge' => "0%",
            'cod_min_surcharge'        => null,
            'price_vs_dest'            => 0,
        ];

        return $rate;

    }


}
