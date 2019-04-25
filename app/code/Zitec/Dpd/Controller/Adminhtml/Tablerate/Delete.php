<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 2/23/2017
 * Time: 6:25 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Zitec\Dpd\Model\Tablerate\Tablerate;

class Delete extends Action
{
    /**
     * @var \Zitec\Dpd\Model\Tablerate\Tablerate
     */
    private $tablerateModel;

    public function __construct(
        Context $context,
        Tablerate $tablerateModel
    ) {
        parent::__construct($context);
        $this->tablerateModel = $tablerateModel;
    }

    public function execute()
    {
        $tablerateId = $this->getRequest()->getParam('tablerate_id');
        if ($tablerateId > 0) {
            try {
                $model = $this->tablerateModel->load($tablerateId);
                $model->delete();
                $this->getMessageManager()->addSuccessMessage(__('Rate was successfully deleted'));
                $this->_redirect('*/*/', ['tablerate_id' => $this->getRequest()->getParam('tablerate_id')]);
            } catch (\Exception $e) {
                $this->getMessageManager()->addErrorMessage($e->getMessage());
                $this->_redirect('*/*/edit', ['tablerate_id' => $this->getRequest()->getParam('tablerate_id')]);
            }
        }

        $this->_redirect('*/*/');
    }
}
