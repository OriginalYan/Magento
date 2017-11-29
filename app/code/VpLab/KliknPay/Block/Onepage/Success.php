<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace VpLab\KliknPay\Block\Onepage;

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
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var \VpLab\Catalog\Helper\GoogleTags
     */
    protected $_googleTagsHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \VpLab\Catalog\Helper\GoogleTags $googleTagsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_objectManager = $objectManager;
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
        $order = $this->_checkoutSession->getLastRealOrder();

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

    /**
     * Return data for Google Tags Ecommerce
     */
    public function getGoogleTagsData()
    {
        $pk = isset($_GET['tnx']) ? intval($_GET['tnx']) : null;
        if (!$pk) {
            return;
        }
        $quote = $this->getQuote($pk);
        if (!$quote) {
            return;
        }
        $order = $this->getOrder($quote);
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

        $items = $this->_googleTagsHelper->getQuoteItems($quote);
        if ($items) {
            $ga['products'] = $items;
        }

        return $ga;
    }

    /**
     * Returns Quote by ID
     */
    public function getQuote($pk)
    {
        if (!intval($pk)) {
            return null;
        }
        return $this->_objectManager->get('Magento\Quote\Model\Quote')->loadByIdWithoutStore($pk);
    }

    /**
     * Returns order by quote
     */
    public function getOrder($quote)
    {
        if (!$quote) {
            return;
        }
        $increment_id = $quote->getReservedOrderId();
        if (!$increment_id) {
            return;
        }
        return $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);
    }
}
