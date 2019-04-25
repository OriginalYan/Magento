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

namespace Zitec\Dpd\Model\Dpd;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
/**
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setReference($reference)
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setDpdId($dpdId)
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setPickupDate($pickupDate)
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setPickupTimeFrom($from)
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setPickupTimeTo($to)
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setCallData($callData)
 * @method  \Zitec\Dpd\Model\Dpd\Pickup setResponseData($responseData)
 */
class Pickup extends \Magento\Framework\Model\AbstractModel
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        $this->_init('Zitec\Dpd\Model\Mysql4\Dpd\Pickup');
    }
}


