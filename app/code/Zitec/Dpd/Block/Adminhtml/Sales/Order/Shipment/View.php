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

namespace Zitec\Dpd\Block\Adminhtml\Sales\Order\Shipment;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class View extends \Magento\Shipping\Block\Adminhtml\View
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
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Helper\Data $backendHelper,
        \Zitec\Dpd\Helper\Data $dpdHelper
    )
    {
        $this->backendHelper = $backendHelper;
        $this->dpdHelper = $dpdHelper;

        parent::__construct($context, $registry);

        if (!$this->dpdHelper->moduleIsActive()) {
            return;
        }

        if (!$this->dpdHelper->isShippingMethodDpd($this->getShipment()->getOrder()->getShippingMethod()) || $this->dpdHelper->isCancelledWithDpd($this->getShipment())) {
            return;
        }


        $isManifestClosed = $this->dpdHelper->isManifestClosed($this->getShipment()->getId());
        if ($isManifestClosed) {
            $onClick = 'setLocation(\'' . $this->_getManifestUrl() . '\')';
        } else {
            $onClick = "deleteConfirm('"
                . __('Once the manifest is closed, you will not be able to make further changes to the shipping address. Do you want to continue?')
                . "', '" . $this->_getManifestUrl() . "')";
        }

        $this->addButton('closemanifest', array(
            'label'      => $isManifestClosed ? __('Print Manifest') : __('Close Manifest'),
            'class'      => 'save',
            'onclick'    => $onClick,
            'sort_order' => -10
        ));
    }

    protected function _getManifestUrl()
    {
        return $this->backendHelper->getUrl("dpd/shipment/manifest", array("shipment_ids" => $this->getShipment()->getId()));
    }
}
