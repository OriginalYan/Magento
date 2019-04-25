<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 2/23/2017
 * Time: 6:30 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;
    /**
     * @var \Magento\Backend\Model\Session
     */
    private $backendSession;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->backendSession = $context->getSession();
    }

    public function execute()
    {
        $this->backendSession->setTablerateData(false);
        $this->_forward('edit');
    }
}
