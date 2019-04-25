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

namespace Zitec\Dpd\Controller\Adminhtml\Tablerate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Zitec\Dpd\Helper\Tablerate\Data;
use Zitec\Dpd\Model\Tablerate\Tablerate;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Edit extends Action
{

    /**
     * @var \Zitec\Dpd\Model\Tablerate\Tablerate
     */
    protected $tablerateModel;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Zitec\Dpd\Helper\TableRate\Data
     */
    protected $tableRatesHelper;

    /** @var PageFactory */
    protected $resultPageFactory;


    /**
     * Edit constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Zitec\Dpd\Model\Tablerate\Tablerate $tablerate
     * @param \Zitec\Dpd\Helper\Tablerate\Data $tableRatesHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        Tablerate $tablerate,
        Data $tableRatesHelper
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->tablerateModel = $tablerate;
        $this->tableRatesHelper = $tableRatesHelper;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $tablerateId = (int)$this->getRequest()->getParam('tablerate_id');

        $tablerate = $this->tablerateModel;

        if ($tablerateId) {
            $tablerate->load($tablerateId);
        }

        $this->registry->register('tablerate_data', $tablerate);

        $pageTitle = $this->tableRatesHelper->getGridTitle();
        $pageTitle .= ' - ' . ($tablerateId ? __('Edit Rate') : __('New Rate'));
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        /**
         * Set active menu item
         */
        $this->_setActiveMenu("Zitec_Dpd::dpd_grid");

        return $resultPage;
    }
}
