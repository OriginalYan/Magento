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

namespace Zitec\Dpd\Block\Adminhtml\Sales\Order\Shipment\View;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Tracking extends \Magento\Shipping\Block\Adminhtml\Order\Tracking\View
{

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    public function __construct(
        \Magento\Backend\Helper\Data $backendHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->backendHelper = $backendHelper;
        $this->dpdHelper = $dpdHelper;
    }
    /**
     * check if the carrier code is one of DPD
     *
     * @return boolean
     */
    public function isDPD()
    {
        $shippingMethod = $this->getShipment()->getOrder()->getShippingMethod();

        return $this->_getHelper()->isShippingMethodDpd($shippingMethod);
    }


    public function getShipInfo()
    {
        return '';
    }


    /**
     *
     * @param type $track
     *
     * @return string
     */
    public function getRemoveUrl($track)
    {
        if ($this->isDpdTrack($track)) {
            $this->backendHelper->getUrl("dpd/shipment/manifest", array("shipment_ids" => $this->getShipment()->getId()));

            return $this->getUrl('dpd/shipment/delete/', array(
                'shipment_id' => $this->getShipment()->getId(),
                'track_id'    => $track->getId()
            ));
        } else {
            return parent::getRemoveUrl($track);
        }
    }


    /**
     *
     * @return \Zitec\Dpd\Helper\Data
     */
    protected function _getHelper()
    {
        return $this->dpdHelper;
    }

    public function isDpdTrack(\Magento\Sales\Model\Order\Shipment\Track $track)
    {
        return $this->_getHelper()->isDpdTrack($track);
    }

}
