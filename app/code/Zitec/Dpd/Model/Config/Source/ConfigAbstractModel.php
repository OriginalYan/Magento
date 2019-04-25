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
class ConfigAbstractModel
{
    /** @var \Zitec\Dpd\Helper\Data */
    private $dpdDataHelper;

    /** @var \Zitec\Dpd\Helper\Ws */
    private $dpdWsHelper;

    public function __construct(
        \Zitec\Dpd\Helper\Data $dpdDataHelper,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper
    ) {
        $this->dpdDataHelper = $dpdDataHelper;
        $this->dpdWsHelper = $dpdWsHelper;
    }

    /**
     * @return \Zitec\Dpd\Helper\Data
     */
    protected function _getHelper()
    {
        return $this->dpdDataHelper;
    }

    /**
     * @return \Zitec\Dpd\Helper\Ws
     */
    protected function _getWsHelper()
    {
        return $this->dpdWsHelper;
    }
}


