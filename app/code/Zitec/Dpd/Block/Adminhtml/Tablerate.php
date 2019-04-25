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

namespace Zitec\Dpd\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Grid\Container;
use Zitec\Dpd\Helper\Tablerate\Data;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Tablerate extends Container
{

    /**
     * @var Data
     */
    protected $tableRatesHelper;

    public function __construct(
        Context $context,
        Data $tableRatesHelper,
        array $data = []
    ) {
        $this->tableRatesHelper = $tableRatesHelper;

        parent::__construct($context, $data);

        $this->_controller     = 'adminhtml_tablerate';
        $this->_blockGroup     = 'Zitec_Dpd';
        $this->_headerText     = $this->_getHelper()->getGridTitle();
        $this->_addButtonLabel = __('Add Rate');

        $this->addButton('zitec_import',
            array(
                'label'   => __('Import Rates'),
                'onclick' => "setLocation('{$this->getImportUrl()}')"
            )
        );

        $this->addButton('zitec_export',
            array(
                'label'   => __('Export Rates'),
                'onclick' => "setLocation('{$this->getExportUrl()}')"
            )
        );
    }

    /**
     *
     * @return \Zitec\Dpd\Helper\Tablerate\Data
     */
    protected function _getHelper()
    {
        return $this->tableRatesHelper;
    }

    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new', array("carrier" => $this->_getHelper()->getCarrierCode()));
    }

    public function getImportUrl()
    {
        return $this->getUrl('*/*/import', array("carrier" => $this->_getHelper()->getCarrierCode()));
    }

    public function getExportUrl()
    {
        return $this->getUrl('*/*/export', array("carrier" => $this->_getHelper()->getCarrierCode()));
    }
}
