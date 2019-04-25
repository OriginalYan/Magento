<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 4/6/2017
 * Time: 8:20 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\ProfitReport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\View\Result\PageFactory;
use Zitec\Dpd\Block\Adminhtml\ShippingReports\Profitability\Grid;

class ExportCsv extends Action
{
    const EXPORT_FILE_NAME = 'zitec_shippingreports_price_vs_cost';

    const GRID_BLOCK = Grid::class;

    /** @var PageFactory */
    protected $resultPageFactory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $fileName = self::EXPORT_FILE_NAME . '.csv';

        $resultPage = $this->resultPageFactory->create();

        /** @var $block Grid */
        $block = $resultPage->getLayout()->createBlock(self::GRID_BLOCK);

        $content = $block->getCsvFile();

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}
