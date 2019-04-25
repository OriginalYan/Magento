<?php

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Zitec\Dpd\Model\Tablerate\Tablerate;

class MassDelete extends Action
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
        $Ids = (array)$this->getRequest()->getParam('tablerates');
        try {
            foreach ($Ids as $id) {
                $result = $this->tablerateModel->load($id);
                $result->delete();
            }
            $this->getMessageManager()->addSuccessMessage(
                __('Total of %1 record(s) have been deleted.', count($Ids))
            );
        } catch (LocalizedException $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->getMessageManager()->addExceptionMessage($e, __('An error occurred while updating records.'));
        }

        $this->_redirect('*/*/');
    }
}
