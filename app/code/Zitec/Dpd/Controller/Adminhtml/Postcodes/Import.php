<?php
/**
 * Created by PhpStorm.
 * User: horatiu.brinza
 * Date: 2/20/2017
 * Time: 6:52 PM
 */

namespace Zitec\Dpd\Controller\Adminhtml\Postcodes;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\File\UploaderFactory;
use Zitec\Dpd\Helper\Postcode\Search;
use Zitec\Dpd\Helper\Tablerate\Data;

class Import extends Action
{
    /**
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    protected $dpdPostcodeSearchHelper;

    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Data
     */
    protected $tableRatesHelper;

    /**
     * @var \Magento\Framework\File\UploaderFactory
     */
    protected $uploaderFactory;


    public function __construct(
        Context $context,
        Search $dpdPostcodeSearchHelper,
        Data $tableRatesHelper,
        UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context);

        $this->uploaderFactory = $uploaderFactory;
        $this->dpdPostcodeSearchHelper = $dpdPostcodeSearchHelper;
        $this->tableRatesHelper = $tableRatesHelper;
    }

    /**
     * upload the file and run the import script on it
     * if a file was already uploaded and the name
     * of the file is sent in the post request then run the import on this file
     */
    public function execute()
    {
        set_time_limit(0);

        $baseFileName = '';
        $newUpdateFilename = '';

        try {

            //process the upload logic for the csv file
            if (isset($_FILES['csv']['name']) && $_FILES['csv']['name'] != '') {
                if (isset($_FILES['csv']['error']) && !empty($_FILES['csv']['error'])) {
                    $message = $this->getUploadCodeMessage($_FILES['csv']['error']);
                    throw new \Exception($message, $_FILES['csv']['error']);
                }
                $uploader = $this->uploaderFactory->create(['fileId' => 'csv']);
                $uploader->setAllowedExtensions(array('csv'));
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);
                $path = $this->dpdPostcodeSearchHelper->getPathToDatabaseUpgradeFiles();

                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                $uploader->save($path, $_FILES['csv']['name']);
                $newUpdateFilename = $path . $uploader->getUploadedFileName();
                $baseFileName      = $uploader->getUploadedFileName();
            }

            // if the no uploads made then check if the path_to_csv field was filed
            if (empty($newUpdateFilename)) {
                $baseFileName = $this->getRequest()->getPost('path_to_csv');
                if (empty($baseFileName)) {
                    throw new \Exception(
                        __('Nothing to do!! Please upload a file or select an uploaded file.')
                    );
                }
                if (isset($baseFileName)) {
                    $path           = $this->dpdPostcodeSearchHelper->getPathToDatabaseUpgradeFiles();
                    $updateFilename = $path . $baseFileName;
                    if (!is_file($updateFilename)) {
                        throw new \Exception(
                            __('File %1 was not found in path media/dpd/postcode_updates', $baseFileName)
                        );
                    }

                    $newUpdateFilename = $updateFilename;
                }
            }

            if (!is_file($newUpdateFilename)) {
                throw new \Exception(
                    __('File %1 was not found in path media/dpd/postcode_updates', $baseFileName)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('dpd/postcodes');

            return;
        }

        // with the filename found, we need to run the database update script
        // by calling the updateDatabase function of the postcode library
        try {
            $this->dpdPostcodeSearchHelper->updateDatabase($newUpdateFilename);
            $this->messageManager->addSuccessMessage(
                __('Last updates found on file %1, were installed successfully.', $baseFileName)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $this->_redirect('dpd/postcodes');
    }


    /**
     *
     * @param $code
     *
     * @return string
     */
    private function getUploadCodeMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                $this->_getSession()->setDpdMaxFileUploadError(ini_get('upload_max_filesize'));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }

        return $message;
    }
}
