<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 2/23/2017
 * Time: 6:26 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Import extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $pageTitle = __('Import Rates');
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        /**
         * Set active menu item
         */
        $this->_setActiveMenu("Zitec_Dpd::table_rates");

        return $resultPage;
    }
}
