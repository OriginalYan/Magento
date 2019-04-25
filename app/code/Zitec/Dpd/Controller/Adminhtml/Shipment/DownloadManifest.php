<?php

namespace Zitec\Dpd\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

class DownloadManifest extends Action
{
    /**
     * @var \Zitec\Dpd\Model\Dpd\ManifestFactory
     */
    private $dpdDpdManifestFactory;
    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Zitec\Dpd\Model\Dpd\ManifestFactory $dpdDpdManifestFactory,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    )
    {
        parent::__construct($context);

        $this->dpdDpdManifestFactory = $dpdDpdManifestFactory;
        $this->dpdHelper = $dpdHelper;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $manifestId = $this->getRequest()->getParam("manifest_id");
        try {
            if (!$manifestId) {
                $message = __("A problem occurred whilst attempting to download a manifest. No manifest was specified in the request.");
                throw new LocalizedException($message);
            }

            $manifest = $this->dpdDpdManifestFactory->create();
            /* @var $manifest \Zitec\Dpd\Model\Dpd\Manifest */
            try {
                $manifest->load($manifestId);
            } catch (\Exception $e) {
                $message = __("A problem occurred whilst attempting to download the manifest id %1: %2", $manifestId, $e->getMessage());
                throw new LocalizedException($message);
            }
            if ($manifest->getManifestId() != $manifestId) {
                $message = __("A problem occurred whilst attempting to download the manifest %1. The manifest no longer exists.", $manifestId);
                throw new LocalizedException($message);
            }
            $pdfFile = base64_decode($manifest->getPdf());
            $pdf     = \Zend_Pdf::parse($pdfFile);

            return $this->fileFactory->create("{$manifest->getManifestRef()}_dpd_manifest.pdf", $pdf->render(), DirectoryList::VAR_DIR, 'application/pdf');
        } catch (LocalizedException $e) {
            $this->dpdHelper->addError($e->getMessage());
            $this->dpdHelper->log($e->getMessage(), __FUNCTION__, __CLASS__, __LINE__);
        } catch (\Exception $e) {
            $message = __("An unexpected problem occurred whilst attempting to download the manifest %1. %2", $manifestId, $e->getMessage());
            $this->dpdHelper->addError($message);
            $this->dpdHelper->log($message, __FUNCTION__, __CLASS__, __LINE__);
        }
        $this->_redirect("sales/shipment");
    }
}
