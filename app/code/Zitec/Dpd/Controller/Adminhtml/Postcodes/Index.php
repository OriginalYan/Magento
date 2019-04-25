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

namespace Zitec\Dpd\Controller\Adminhtml\Postcodes;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;


/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Index extends Action
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
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        // if an error occurred in the past because of php upload size limit, then trigger an error message
        // while the setting is not modified
        $fileSizeError   = $this->_getSession()->getDpdMaxFileUploadError();
        $currentSettings = ini_get('upload_max_filesize');
        if (!empty($fileSizeError) && $fileSizeError == $currentSettings) {
            $this->messageManager->addErrorMessage(
                __('Your PHP settings for upload_max_filesize is too low (%1). Please increase this limit or upload the file manually into media/dpd/postcode', $fileSizeError)
            );
        }

        $resultPage = $this->resultPageFactory->create();

        $layout = $resultPage->getLayout();
        $this->_addContent($layout->createBlock(\Zitec\Dpd\Block\Adminhtml\Postcode\FormContainer::class));
        $this->_addContent($layout->createBlock(\Zitec\Dpd\Block\Adminhtml\Postcode\Update\Files::class));

        $this->_setActiveMenu("Zitec_Dpd::postcode_updater");

        return $resultPage;
    }
}
