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
class Country implements\Magento\Framework\Option\ArrayInterface
{

    protected static $_allAllowedCountries = null;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /** @var \Magento\Directory\Model\Config\Source\Country */
    protected $country;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\Config\Source\Country $country
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->country = $country;
    }

    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        $options = $this->country->toOptionArray($isMultiselect, $foregroundCountries);
        $allowedCountries = $this->getAllAllowedCountries();
        if ($allowedCountries) {
            foreach ($options as $key => $option) {
                if ($option['value'] && !in_array($option['value'], $allowedCountries)) {
                    unset($options[$key]);
                }
            }
        }

        return $options;
    }

    /**
     *
     * @return array
     */
    public function getAllAllowedCountries()
    {
        if (!is_array(self::$_allAllowedCountries)) {
            $allAllowedCountries = trim($this->scopeConfig->getValue("payment/zitec_dpd_cashondelivery/all_allowed_countries", \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            if ($allAllowedCountries) {
                self::$_allAllowedCountries = explode(",", $this->scopeConfig->getValue("payment/zitec_dpd_cashondelivery/all_allowed_countries", \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            } else {
                self::$_allAllowedCountries = array();
            }
        }

        return self::$_allAllowedCountries;
    }


}


