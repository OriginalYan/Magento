<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 2/23/2017
 * Time: 9:02 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\DataObject;
use Zitec\Dpd\Helper\Tablerate\Data;

class ImportRates extends Action
{
    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Data
     */
    private $tableRatesHelper;

    public function __construct(
        Context $context,
        Data $tableRatesHelper
    ) {
        parent::__construct($context);

        $this->tableRatesHelper = $tableRatesHelper;
    }

    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website_id');
        $csvFile = !empty($_FILES['import']['tmp_name']) ? $_FILES['import']['tmp_name'] : null;
        if (!$websiteId || !$csvFile) {
            $this->_getSession()->addError(__("Please specify the website and file you wish to import"));
            $this->_redirect('*/*/import', ["carrier" => $this->tablerateHelper->getCarrierCode()]);

            return;
        }

        $params = $this->_objectManager->create(DataObject::class);
        $params->setScopeId($websiteId);

        $resourceClass = null;
        $method = null;
        $this->tableRatesHelper->getImportAction($resourceClass, $method);


        $message = "";
        try {
            $this->_objectManager->create($resourceClass)->$method($params, $csvFile);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage(__("An error occurred whilst importing the tablerates: %1", $e->getMessage()));
            $this->_redirect('*/*/import');

            return;
        }
        if (!$message) {
            $message = __("Table rates imported successfully");
            $this->getMessageManager()->addSuccessMessage($message);
        } else {
            $this->getMessageManager()->addErrorMessage(str_replace("\n", "<br />", $message));
        }
        $this->_redirect('*/*/index');
    }
}
