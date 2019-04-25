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

namespace Zitec\Dpd\Block\Adminhtml\ShippingReports;

use Magento\Backend\Block\Widget\Grid\Container;


/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Profitability extends Container
{

    public function _construct()
    {
        parent::_construct();

        /** @var \Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability\Grid */
        $this->_controller = 'adminhtml_shippingReports_profitability';
        $this->_blockGroup = 'Zitec_Dpd';
        $this->_headerText = __('Shipping Price vs Cost');

        $this->removeButton('add');
    }
}
