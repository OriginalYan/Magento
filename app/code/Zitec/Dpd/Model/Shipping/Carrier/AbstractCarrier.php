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

namespace Zitec\Dpd\Model\Shipping\Carrier;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
abstract class AbstractCarrier extends \Magento\Shipping\Model\Carrier\AbstractCarrier
{
    protected $_numBoxes = 1;

    /**
     * Check if carrier has shipping label option available
     *
     * @return boolean
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }


    public function isTrackingAvailable()
    {
        return true;
    }

    public function isCityRequired()
    {
        return true;
    }

    public function isZipCodeRequired($countryId = null)
    {
        return true;
    }

    public function getTotalNumOfBoxes($weight)
    {
        /*
        reset num box first before retrieve again
        */
        $this->_numBoxes = 1;
        //$weight = $this->convertWeightToLbs($weight);
        $maxPackageWeight = $this->getConfigData('max_package_weight');
        if ($weight > $maxPackageWeight && $maxPackageWeight != 0) {
            $this->_numBoxes = ceil($weight / $maxPackageWeight);
            $weight          = $weight / $this->_numBoxes;
        }

        return $weight;
    }
}
