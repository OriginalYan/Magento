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

namespace Zitec\Dpd\Block\Adminhtml\Tablerate\Export;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\FormFactory;
use Zitec\Dpd\Model\Tablerate\Source\Website;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Form extends \Magento\Backend\Block\Widget\Form
{

    /**
     * @var \Zitec\Dpd\Model\Tablerate\Source\Website
     */
    protected $tableRatesSourceWebsite;

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Zitec\Dpd\Model\Tablerate\Source\Website $tableRatesSourceWebsite
     * @param \Magento\Framework\Data\FormFactory $formFactory
     */
    public function __construct(
        Context $context,
        Website $tableRatesSourceWebsite,
        FormFactory $formFactory
    ) {
        parent::__construct($context);

        $this->formFactory = $formFactory;
        $this->tableRatesSourceWebsite = $tableRatesSourceWebsite;
    }

    protected function _prepareForm()
    {
        $form = $this->formFactory->create([
            'data' => [
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/*/exportrates', [
                    'tablerate_id' => $this->getRequest()->getParam('tablerate_id')
                ]),
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
            ]
        ]);
        $this->setForm($form);


        $fieldset = $form->addFieldset('base_fieldset', []);

        $fieldset->addField('website_id', 'select', [
            'name'     => 'website_id',
            'label'    => __('Website'),
            'values'   => $this->tableRatesSourceWebsite->toOptionArray(),
            'required' => true
        ]);


        $form->setUseContainer(true);

        return parent::_prepareForm();
    }

    public function getExportUrl()
    {
        return $this->getUrl('*/*/exportrates');
    }
}

