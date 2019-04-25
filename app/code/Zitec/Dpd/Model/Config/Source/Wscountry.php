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

use Magento\Directory\Model\Config\Source\Country;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Wscountry extends ConfigAbstractModel
{

    const WS_COUNTRY_OTHER = 'zOther';

    /**
     * @var Country
     */
    protected $modelConfigSourceCountry;

    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        $dataHelper = $this->_getHelper();

        $configCountry = $dataHelper->getConfigCountry();
        $countries = $configCountry->toOptionArray($isMultiselect, $foregroundCountries);

        // Only include the countries that have web service URLs defined.
        foreach ($countries as $key=>$country) {
            if (!empty($country['value']) && !$this->_getWsHelper()->hasWsUrls($country['value'])) {
                unset($countries[$key]);
            }
        }

        $countries[self::WS_COUNTRY_OTHER] = ['value' => self::WS_COUNTRY_OTHER, 'label' => __('Other (enter web service URLs manually)')];

        return $countries;
    }
}


