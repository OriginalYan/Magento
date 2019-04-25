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

namespace Zitec\Dpd\Block\Adminhtml\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Export extends Field
{

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    public function __construct(
        \Magento\Backend\Helper\Data $backendHelper
    ) {
        $this->backendHelper = $backendHelper;
    }
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $buttonBlock = $element->getForm()->getParent()->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website')
        );

        $data = array(
            'label'   => __('Export CSV'),
            'onclick' => 'setLocation(\'' . $this->backendHelper->getUrl("zitec_dpd/adminhtml_config/exportTablerates", $params) . '\')',
            'class'   => '',
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }

}


