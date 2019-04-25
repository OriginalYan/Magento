<?php

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Zitec\Dpd\Model\Tablerate\Tablerate;
use Zitec\Dpd\Helper\Tablerate\Data;

class Save extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;
    /**
     * @var \Zitec\Dpd\Helper\Tablerate\Data
     */
    private $tablerateHelper;
    /**
     * @var \Zitec\Dpd\Model\Mysql4\Tablerate
     */
    private $tablerateResourceModel;
    /**
     * @var \Magento\Backend\Model\Session
     */
    private $backendSession;
    /**
     * @var \Zitec\Dpd\Model\Tablerate\Tablerate
     */
    private $tablerateModel;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $tablerateHelper,
        Tablerate $tablerateModel,
        \Zitec\Dpd\Model\Mysql4\Tablerate $tablerateResourceModel
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->backendSession = $context->getSession();
        $this->tablerateHelper = $tablerateHelper;
        $this->tablerateModel = $tablerateModel;
        $this->tablerateResourceModel = $tablerateResourceModel;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPost()->toArray();

        if ($data) {
            if ($this->getRequest()->getParam("duplicate") && isset($data['pk'])) {
                unset($data['pk']);
                $this->getMessageManager()->addSuccessMessage(__('Rate duplicated successfully'));
                $this->backendSession->setTablerateData($data);
                $this->_redirect("*/*/edit");

                return;
            }

            $tablerate = $this->tablerateModel;

            $this->_prepareSaveData($tablerate, $data);
            try {
                $tablerate->save();

                $this->getMessageManager()->addSuccessMessage(__('Rate was successfully saved'));
                $this->backendSession->setTablerateData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('tablerate_id' => $tablerate->getTablerateId()));

                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                if ($this->tablerateHelper->isMySqlDuplicateKeyErrorMessage($e->getMessage())) {
                    $message = __("The rate could not be saved because it duplicates the destination, service/product and weight/price of an existing rate. Change some of the rate's values and try saving again.");
                } else {
                    $message = $e->getMessage();
                }
                $this->getMessageManager()->addErrorMessage($message);
                $this->backendSession->setTablerateData($data);
                $this->_redirect('*/*/edit', array('tablerate_id' => $this->getRequest()->getParam('tablerate_id')));

                return;
            }
        }
        $this->getMessageManager()->addErrorMessage(__('Unable to find rate to save'));
        $this->_redirect('*/*/');
    }

    /**
     *
     * @param Tablerate $tablerate
     * @param array                            $data
     *
     * @return boolean
     */
    protected function _prepareSaveData(Tablerate $tablerate, array $data)
    {
        if (isset($data['pk']) && !$data['pk']) {
            unset($data['pk']);
        }

        $data['dest_zip'] = isset($data['dest_zip']) && $data['dest_zip'] != '*' ? $data['dest_zip'] : '';

        if (!$this->tablerateHelper->supportsProduct() && isset($data['product'])) {
            unset($data['product']);
        }

        if ($this->tablerateHelper->supportsPriceVsDest()) {
            $data['price_vs_dest'] = isset($data['price_vs_dest']) ? $data['price_vs_dest'] : '0';
        } elseif (isset($data['price_vs_dest'])) {
            unset($data['price_vs_dest']);
        }

        $data['weight_price'] = isset($data['weight_price']) && trim($data['weight_price']) ? $data['weight_price'] : '0';

        if (!isset($data['shipping_method_enabled']) || $data['shipping_method_enabled']) {
            if ($this->tablerateHelper->supportsCashOnDelivery()) {
                if (!isset($data['cod_option'])) {
                    $data['cod_option'] = Tablerate::COD_NOT_AVAILABLE;
                }
                switch ($data['cod_option']) {
                    case Tablerate::COD_NOT_AVAILABLE:
                        $data['cashondelivery_surcharge'] = null;
                        if ($this->tablerateHelper->supportsCodMinSurcharge()) {
                            $data['cod_min_surcharge'] = null;
                        }
                        break;
                    case Tablerate::COD_SURCHARGE_ZERO:
                        $data['cashondelivery_surcharge'] = '0';
                        if ($this->tablerateHelper->supportsCodMinSurcharge()) {
                            $data['cod_min_surcharge'] = null;
                        }
                        break;
                    case Tablerate::COD_SURCHARGE_FIXED:
                        if (!isset($data['cashondelivery_surcharge']) || !trim($data['cashondelivery_surcharge'])) {
                            $data['cashondelivery_surcharge'] = '0';
                        }
                        if ($this->tablerateHelper->supportsCodMinSurcharge()) {
                            $data['cod_min_surcharge'] = null;
                        }
                        break;
                    case Tablerate::COD_SURCHARGE_PERCENTAGE:
                        if (!isset($data['cashondelivery_surcharge']) || !trim($data['cashondelivery_surcharge'])) {
                            $data['cashondelivery_surcharge'] = '0';
                        }
                        $data['cashondelivery_surcharge'] = $data['cashondelivery_surcharge'] ? $data['cashondelivery_surcharge'] . '%' : '0';
                        if ($this->tablerateHelper->supportsCodMinSurcharge()) {
                            $data['cod_min_surcharge'] = isset($data['cod_min_surcharge']) && trim($data['cod_min_surcharge']) ? $data['cod_min_surcharge'] : null;
                        }
                        break;
                    default:
                        $data['cashondelivery_surcharge'] = null;
                        if ($this->tablerateHelper->supportsCodMinSurcharge()) {
                            $data['cod_min_surcharge'] = null;
                        }
                }
            }
        } else {
            if ($this->tablerateHelper->supportsMarkup()) {
                $data['markup_type'] = '0';
            }
            $data['price'] = -1;
            if ($this->tablerateHelper->supportsCashOnDelivery()) {
                $data['cashondelivery_surcharge'] = null;
            }
            if ($this->tablerateHelper->supportsCodMinSurcharge()) {
                $data['cod_min_surcharge'] = null;
            }
        }

        if (isset($data['shipping_method_enabled'])) {
            unset($data['shipping_method_enabled']);
        }

        if (!$this->tablerateHelper->supportsMarkup() && isset($data['markup_type'])) {
            unset($data['markup_type']);
        }


        if (isset($data['cod_option'])) {
            unset($data['cod_option']);
        }

        if (!$this->tablerateHelper->supportsCashOnDelivery() && isset($data['cashondelivery_surcharge'])) {
            unset($data['cashondelivery_surcharge']);
        }

        if (!$this->tablerateHelper->supportsCodMinSurcharge() && isset($data['cod_min_surcharge'])) {
            unset($data['cod_min_surcharge']);
        }


        $saveData = array();
        foreach ($this->_getMap() as $logicalName => $dbFieldName) {
            $saveData[$dbFieldName] = isset($data[$logicalName]) ? $data[$logicalName] : null;
        }

        $tablerate->setData($saveData);

        return true;
    }

    /**
     *
     * @return array
     */
    private function _getMap()
    {
        return $this->tablerateResourceModel->getLogicalDbFieldNamesMap();
    }
}
