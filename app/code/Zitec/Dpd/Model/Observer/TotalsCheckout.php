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

namespace Zitec\Dpd\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class TotalsCheckout implements ObserverInterface
{

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Magento\Checkout\Model\CartFactory
     */
    protected $checkoutCartFactory;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Magento\Checkout\Model\CartFactory $checkoutCartFactory,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->dpdHelper = $dpdHelper;
        $this->checkoutCartFactory = $checkoutCartFactory;
        $this->request = $request;
    }
    /**
     * force collect totals on checkout if the payment method is DPD
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $input = $observer->getEvent()->getInput();
        if ($input->getMethod() == $this->dpdHelper->getDpdPaymentCode()) {
            $this->checkoutCartFactory->create()->getQuote()->setTotalsCollectedFlag(false);
        }

        $this->refreshTotalsInAdminOrderCreate($observer);
    }

    /**
     * force collect html for review order in admin order create
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    private function refreshTotalsInAdminOrderCreate(\Magento\Framework\Event\Observer $observer)
    {
        $request = $this->request;
        $payment = $request->getParam('payment');
        if (!empty($payment)) {
            $block  = $request->getParam('block');
            $blocks = explode(',', $block);
            if (!in_array('totals', $blocks)) {
                $request->setParam('block', $request->getParam('block') . ',totals');
            }
        }
    }


}
