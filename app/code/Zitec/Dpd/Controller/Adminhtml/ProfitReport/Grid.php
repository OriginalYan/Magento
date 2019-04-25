<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 4/8/2017
 * Time: 10:32 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\ProfitReport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Grid extends Action
{
    /** @var PageFactory */
    protected $resultPageFactory;

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

        $layout = $resultPage->getLayout();

        $this->getResponse()->setBody(
            $layout->createBlock(\Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability\Grid::class)->toHtml()
        );

        return $resultPage;
    }
}
