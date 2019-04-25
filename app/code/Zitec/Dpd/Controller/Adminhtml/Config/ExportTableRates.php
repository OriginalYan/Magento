<?php

namespace Zitec\Dpd\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Zitec\Dpd\Block\Adminhtml\Tablerate\GridExport;


class ExportTableRates extends Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $fileName  = 'dpd_tablerates.csv';

        $layout = $this->_view->getLayout();
        $gridBlock = $layout->createBlock(GridExport::class);
        $website   = $this->storeManager->getWebsite($this->getRequest()->getParam('website'));
        $gridBlock->setWebsiteId($website->getId());

        $content = $gridBlock->getCsvFile();

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}
