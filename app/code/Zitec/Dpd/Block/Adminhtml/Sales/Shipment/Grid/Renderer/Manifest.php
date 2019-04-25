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

namespace Zitec\Dpd\Block\Adminhtml\Sales\Shipment\Grid\Renderer;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Manifest extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    public function __construct(
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->dpdHelper = $dpdHelper;
    }
    public function render(\Magento\Framework\DataObject $row)
    {
        $manifestId  = $row->getData('zitec_manifest_id');
        $manifestRef = $row->getData('zitec_manifest_ref');
        if ($manifestId && $manifestRef) {
            $url = $this->dpdHelper->getDownloadManifestUrl($manifestId);

            return "<a href='{$url}'>{$this->dpdHelper->escapeHtml($manifestRef)}</a>";
        } else {
            return '';
        }

    }
}


