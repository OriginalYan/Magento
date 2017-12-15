<?php

namespace VpLab\YandexKassa\Model;

use Exception;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Notification
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \VpLab\YandexKassa\Model\YandexKassa
     */
    protected $vplabYandex;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $_cartManagement;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $_orderSender;
    protected $_paymentMethod;
    protected $_quote;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \VpLab\YandexKassa\Model\YandexKassa $vplabYandex,
        OrderSender $orderSender,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_objectManager = $objectManager;
        $this->_orderFactory = $orderFactory;
        $this->_vplabYandex = $vplabYandex;
        $this->_orderSender = $orderSender;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_invoiceSender = $invoiceSender;
        $this->_quoteRepository = $quoteRepository;
        $this->_cartManagement = $cartManagement;
        $this->_checkoutSession = $checkoutSession;
    }

    public function processNotification($params)
    {
        if (isset($params['tnx_id']) and trim($params['tnx_id'])) {
            $quote_id = trim($params['tnx_id']);
        // } elseif (isset($params['orderNumber']) and trim($params['orderNumber'])) {
        //     $quote_id = trim($params['orderNumber']);
        } else {
            throw new Exception('[YANDEX] Unknown Order ID in notification request');
        }

        $this->setPaymentMethod();

        $this->_quote = $this->_getQuote($quote_id);
        $this->_quote->setPaymentMethod($this->_paymentMethod->getCode());
        $this->_quote->getPayment()->importData(['method' => $this->_paymentMethod->getCode()]);
        $this->_quote->save();

        $this->initCheckout();
        try {
            $this->_cartManagement->placeOrder($this->_quote->getId(), $this->_quote->getPayment());
            $this->_order = $this->_getOrder();
        } catch (\Exception $e) {
            $this->_order = $this->_getOrder($this->_quote->getReservedOrderId());
        }
        if (!$this->_order) {
            throw new Exception(sprintf('Could not locate order for Quote: "%s".', $params['tnx']));
        }
        $payment = $this->_order->getPayment();
        $this->_paymentMethod->postProcessing($this->_order, $payment, $params);

        try {
            $this->_processOrderCreated($params);

            $this->_quote->setIsActive(false);

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $comment = $this->_createNotificationComment(sprintf('Error: "%s"', $e->getMessage()));
            $comment->save();
            throw $e;
        }
    }

    /**
     * Load quote by ID
     */
    protected function _getQuote($quoteId)
    {
        $quote = $this->_quoteRepository->get($quoteId);
        if (!$quote) {
            throw new Exception(sprintf('Wrong quote ID: "%s".', $quoteId));
        }
        return $quote;
    }

    protected function _getOrder($orderId = null)
    {
        if ($orderId === null) {
            $orderId = $this->_checkoutSession->getLastRealOrderId();
        }
        return $this->_orderFactory->create()->loadByIncrementId($orderId);
    }

    protected function _processOrderCreated($params)
    {
        $this->_createTransaction($params);

        $this->_order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE)->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
        $this->_order->save();

        $comment = $this->_createNotificationComment(sprintf('ORDER CREATED: "%s"', $params['invoiceId']));
        $comment->save();

        $res = $this->_orderSender->send($this->_order, true);
    }

    protected function _createNotificationComment($comment)
    {
        $message = sprintf('[YandexKassa Notification Processed] "%s"', $comment);
        $message = $this->_order->addStatusHistoryComment($message);
        $message->setIsCustomerNotified(null);
        return $message;
    }

    protected function setPaymentMethod()
    {
        $this->_paymentMethod = $this->_vplabYandex;
    }

    protected function _createTransaction($params)
    {
        $payment = $this->_order->getPayment();
        $payment->setLastTransId($params['invoiceId']);
        $payment->setTransactionId($params['invoiceId']);
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $params]
        );
        $formatedPrice = $this->_order->getBaseCurrency()->formatTxt($params['shopSumAmount']);
        $message = __('The authorized amount is %1.', $formatedPrice);

        // Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($params['invoiceId'])
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $params]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder($transaction, $message);

        $payment->setParentTransactionId(null);
        $payment->save();

        $this->_order->save();

        $this->_createInvoice($payment);
    }

    protected function _createInvoice($payment)
    {
        try {
            if ($this->_order->canInvoice()) {
                $invoice = $this->_objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($this->_order);
                if (!$invoice->getTotalQty()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('You can\'t create an invoice without products.')
                    );
                }
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();

                // Save the invoice to the order
                $transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transaction->save();

                // Magento\Sales\Model\Order\Email\Sender\InvoiceSender
                $this->_invoiceSender->send($invoice, true);

                $message = sprintf('[YandexKassa Notification Processed] You notified customer about invoice #%s.', $invoice->getId());
                $comment = $this->_order->addStatusHistoryComment($message);
                $comment->setIsCustomerNotified(true);
                $comment->save();
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('Error Creating Invoice: "%s"', $e->getMessage()));
        }
    }

    protected function initCheckout()
    {
        if (!$this->_quote->hasItems() or $this->_quote->getHasError()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize checkout.'));
        }
    }
}
