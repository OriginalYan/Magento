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

namespace Zitec\Dpd\Helper;

/**
 * this class is used for
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Payment extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $backendSessionQuote;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session\Quote $backendSessionQuote,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->backendSessionQuote = $backendSessionQuote;
        $this->checkoutSession = $checkoutSession;
        $this->dpdHelper = $dpdHelper;
    }



    public function getWebsiteId()
    {
        if ($this->dpdHelper->isAdmin()) {
            $sessionQuote = $this->backendSessionQuote;
            $store        = $sessionQuote->getStore();
            if (empty($store)) {
                return 0;
            }
            $webSiteId = $store->getWebsiteId();

            return $webSiteId;
        }

        $webSiteId = $this->storeManager->getStore()->getWebsiteId();

        return $webSiteId;
    }



    public function getQuote(){
        if ($this->dpdHelper->isAdmin()) {
            return $this->backendSessionQuote->getQuote();
        } else {
           return $this->checkoutSession->getQuote();
        }
    }

}
