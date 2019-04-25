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

namespace Zitec\Dpd\Block\Adminhtml\Postcode\UpdateForm;

use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Framework\Data\FormFactory;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Form extends WidgetForm
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->formFactory = $formFactory;
    }

    /**
     * prepare form fields
     *
     * @return \Magento\Backend\Block\Widget\Form
     * @throws \Exception
     */
    protected function _prepareForm()
    {
        $form = $this->formFactory->create([
            'data' => [
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/postcodes/import',
                    ['id' => $this->getRequest()->getParam('id')]),
                'enctype' => 'multipart/form-data',
                'method'  => 'post',
            ]
        ]);

        $fieldset = $form->addFieldset(
            'csv_upload',
            ['legend' => 'Upload a CSV to update the postcode database - DPD']
        );
        $fieldset->addField('csv', 'file',
            [
                'label' => 'CSV file received from DPD',
                'name'  => 'csv'
            ]);

        $fieldset = $form->addFieldset('csv_file_path', ['legend' => ' Import an existing file']);
        $fieldset->addField('path_to_csv', 'text',
            [
                'label' => 'File name of the CSV found in media/dpd/postcode_update',
                'name'  => 'path_to_csv'
            ]
        );


        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}


