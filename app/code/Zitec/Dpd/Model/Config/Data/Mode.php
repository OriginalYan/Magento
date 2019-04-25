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

namespace Zitec\Dpd\Model\Config\Data;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Mode extends \Zitec\Dpd\Model\Config\Data\ConfigDataAbstract
{


    /**
     * Validates that the country selected has a WS Url for the mode (production or test)
     *
     * @return void
     */
    protected function _afterSave()
    {
        if (!$this->_isActive()) {
            return;
        }

        $countryId = $this->_getCountryId();
        if (!$countryId || $countryId == \Zitec\Dpd\Model\Config\Source\Wscountry::WS_COUNTRY_OTHER) {
            return;
        }

        if ($this->getValue()) { // Production mode
            if (!$this->dpdWsHelper->hasWsProductionUrl($countryId)) {
                throw new \Magento\Framework\Exception\LocalizedException(__("The country you have selected does not have a web service URL for production mode. Please ensure you have selected the correct country, or select 'Other (enter web service URLs manually)' to enter a specific URL."));
            }
        } else { // Test mode
            if (!$this->dpdWsHelper->hasWsTestUrl($countryId)) {
                throw new \Magento\Framework\Exception\LocalizedException(__("The country you have selected does not have a web service URL for test mode. Please ensure you have selected the correct country, or select 'Other (enter web service URLs manually)' to enter a specific URL."));
            }
        }
    }

    /**
     *
     * @return string
     */
    protected function _getCountryId()
    {
        return $this->_getConfigValue('wscountry');
    }


}

