<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace VpLab\Catalog\Block\Onepage;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;

/**
 * One page checkout success page
 */
class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \VpLab\Catalog\Helper\GoogleTags
     */
    protected $_googleTagsHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \VpLab\Catalog\Helper\GoogleTags $googleTagsHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \VpLab\Catalog\Helper\GoogleTags $googleTagsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_googleTagsHelper = $googleTagsHelper;
    }

    /**
     * Render additional order information lines and return result html
     *
     * @return string
     */
    public function getAdditionalInfoHtml()
    {
        return $this->_layout->renderElement('order.success.additional.info');
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order = $this->getOrder();

        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/order/view/',
                    ['order_id' => $order->getEntityId()]
                ),
                'print_url' => $this->getUrl(
                    'sales/order/print',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'can_view_order'  => $this->canViewOrder($order),
                'order_id'  => $order->getIncrementId()
            ]
        );
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
            && $this->isVisible($order);
    }

    protected function getOrder()
    {
        return $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Return data for Google Tags Ecommerce
     */
    public function getGoogleTagsData()
    {
        $order = $this->getOrder();
        if (!$order) {
            return;
        }
        $payment = $order->getPayment();
        $trans_id = '';
        if ($payment) {
            $trans_id = $payment->getLastTransId();
        }

        $ga = [
            'actionField' => [
                'id' => trim($trans_id) ? trim($trans_id) : $order->getId(),
                'affiliation' => 'newversion.vplaboratory.com',
                'revenue' => number_format($order->getGrandTotal(), 2, '.', ''),
                'shipping' => number_format($order->getShippingAmount(), 2, '.', ''),
                'tax' => number_format($order->getTaxAmount(), 2, '.', ''),
            ],
        ];

        $items = $this->_googleTagsHelper->getQuoteItems($order);
        if ($items) {
            $ga['products'] = $items;
        }

        return $ga;
    }
}
