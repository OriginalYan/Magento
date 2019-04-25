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

namespace Zitec\Dpd\Block\Adminhtml\Tablerate;

use Magento\Backend\Block\Widget\Context;
use Zitec\Dpd\Helper\Tablerate\Data;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Export extends \Magento\Backend\Block\Widget\Form\Container
{

    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Data
     */
    protected $tableRatesHelper;

    public function __construct(
        Context $context,
        Data $tableRatesHelper
    ) {
        $this->tableRatesHelper = $tableRatesHelper;
        $this->_objectId   = 'tablerate_id';
        $this->_blockGroup = 'zitec_dpd';
        $this->_controller = 'adminhtml_tablerate';
        $this->_mode       = 'export';
        parent::__construct($context);

        $this->updateButton('save', 'label', __('Export'));
        $this->removeButton('delete');
        $this->removeButton('reset');
    }

    public function getHeaderText()
    {
        return __('Export') . ' ' . $this->tableRatesHelper->getGridTitle();
    }
}

