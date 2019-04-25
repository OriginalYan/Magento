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

namespace Zitec\Dpd\Controller\Adminhtml\Shipment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;

/**
 * Ajax calls dialog packed shipment are handled.
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Index extends Action
{
    /*
     * A block is loaded for dialogue address validation
     * or nothing, if the address is sent as valid or if there is nothing to do.
     */

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layoutInterface;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Framework\View\LayoutInterface $layoutInterface,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->salesOrderFactory = $salesOrderFactory;
        $this->layoutInterface = $layoutInterface;
        $this->jsonHelper = $jsonHelper;
        $this->priceCurrency = $priceCurrency;
    }
    public function addressvalidationdialoghtmlAction()
    {

        $orderId = $this->getRequest()->getParam('order');
        $order   = $this->salesOrderFactory->create();
        if ($orderId) {
            $order->load($orderId);
        }

        $city               = $this->getRequest()->getParam('city');
        $city               = $city ? $city : '';
        $postcode           = $this->getRequest()->getParam('postcode');
        $postcode           = $postcode ? $postcode : '';
        $countryId          = $this->getRequest()->getParam('countryid');
        $countryId          = $countryId ? $countryId : '';
        $dontCorrectAddress = $this->getRequest()->getParam('dontcorrectaddress');


        if (!$dontCorrectAddress) {
            $layout = $this->layoutInterface;
            $layout->createBlock('zitec_packedshipment/addressvalidationdialog', 'root')
                ->setTemplate('zitec_packedshipment/sales/order/shipment/create/address_validation_dialog.phtml')
                ->setOrder($order)
                ->setCity($city)
                ->setPostcode($postcode)
                ->setCountryId($countryId);
            $dialogHtml         = $layout->addOutputBlock('root')
                ->setDirectOutput(false)
                ->getOutput();
            $data['dialogHtml'] = trim($dialogHtml);
        } else // User has indicated that it wants to make more changes to the address.
        {
            $data['dialogHtml'] = '';
        }
        $this->getResponse()->setHeader('Content-Type', 'application/json', true)->setBody($this->jsonHelper->jsonEncode($data));
    }

    /*
     * The cost of transportation of packages shipping is returned.
     * this function receives the package weight, parcels, zip code of the desitnation

     * @return float
     */
    public function getshippingcostAction()
    {
        $orderId = $this->getRequest()->getParam('order');
        $order   = $this->salesOrderFactory->create()->load($orderId);
        $carrier = $order->getShippingCarrier();
        if (! ($carrier instanceof \Zitec\Dpd\Model\Carrier\CarrierInterface) || !$carrier->supportsCalculationOfShippingCosts() ) {
            $data          = array();
            $data['error'] = __('An attempt to calculate the shipping cost, but the carrier does not support this operation.');
            $this->getResponse()->setHeader('Content-Type', 'application/json', true)->setBody($this->jsonHelper->jsonEncode($data));

        }

        $weightsParcels = $this->getRequest()->getParam('weightsParcels');

        $shippingAddress = $order->getShippingAddress();

        $city     = $this->getRequest()->getParam('city');
        $city     = $city ? $city : $shippingAddress->getCity();
        $postcode = $this->getRequest()->getParam('postcode');
        $postcode = $postcode ? $postcode : $shippingAddress->getPostcode();

        $errorStr = '';
        $shippingCost = $carrier->getShippingCost(
            $order,
            $city,
            $postcode,
            $weightsParcels,
            $errorStr);

        $data                 = array();
        $data['shippingcost'] = $this->priceCurrency->currency($shippingCost, true, false);

        // Shipping cost is returned to store for reports
        // Shipping module Reports.
        $data['shippingreportsshippingcost'] = $shippingCost;

        $profit         = $order->getBaseShippingAmount() - $shippingCost;
        $data['profit'] = $this->priceCurrency->currency($profit, true, false);

        $data['profitcolor'] = $profit >= 0 ? 'Black' : 'Red';

        $data['error'] = $errorStr;

        $this->getResponse()->setHeader('Content-Type', 'application/json', true)->setBody($this->jsonHelper->jsonEncode($data));
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        // TODO: Implement execute() method.
    }
}

