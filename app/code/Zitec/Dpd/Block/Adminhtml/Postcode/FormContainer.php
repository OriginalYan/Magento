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

namespace Zitec\Dpd\Block\Adminhtml\Postcode;

use Magento\Backend\Block\Widget\Form\Container;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class FormContainer extends Container
{
    public function __construct(\Magento\Backend\Block\Widget\Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->_objectId   = 'id';
        $this->_blockGroup = 'zitec_dpd';
        $this->_controller = 'adminhtml_postcode';
        $this->_mode       = 'updateForm';
        $this->_headerText = __('Update postcode database - for DPD Carrier');

        $this->updateButton('save', 'label', __('Import'));
    }

    public function getBackUrl()
    {
        return $this->getUrl('cms/block');
    }
}
