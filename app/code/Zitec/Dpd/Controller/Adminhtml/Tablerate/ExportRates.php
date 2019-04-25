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
use Zitec\Dpd\Helper\Tablerate\Data;

class ExportRates extends Action
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
        if (!$websiteId) {
            $this->getMessageManager()
                ->addErrorMessage(__("Please specify the website whose rates you want to export"));
            $this->_redirect('*/*/export');

            return;
        }

        $module = null;
        $controller = null;
        $action = null;
        $exportAction = $this->tableRatesHelper->getExportAction($module, $controller, $action);
        $params = ['website' => $websiteId];

        if (!$this->tableRatesHelper->isExportUsingRedirect()) {
            $this->_forward($action, $controller, $module, $params);
        } else {
            $this->_redirect($exportAction, $params);
        }

    }
}
