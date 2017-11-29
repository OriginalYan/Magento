<?php

namespace VpLab\KliknPay\Model;

use Exception;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Notification
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \VpLab\KliknPay\Model\KliknPay
     */
    protected $vplabKliknPay;

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
        \VpLab\KliknPay\Model\KliknPay $vplabKliknPay,
        OrderSender $orderSender,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_objectManager = $objectManager;
        $this->_orderFactory = $orderFactory;
        $this->_vplabKliknPay = $vplabKliknPay;
        $this->_orderSender = $orderSender;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_invoiceSender = $invoiceSender;
        $this->_quoteRepository = $quoteRepository;
        $this->_cartManagement = $cartManagement;
        $this->_checkoutSession = $checkoutSession;
    }

    public function processNotification($params)
    {
        $this->setPaymentMethod();

        if (!isset($params['RESPONSE']) or trim($params['RESPONSE']) != '00') {
            throw new Exception(sprintf('Wrong response code from KliknPay for Quote: "%s".', $params['tnx']));
        }

        $this->_quote = $this->_getQuote($params['tnx']);
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

        $comment = $this->_createNotificationComment(sprintf('ORDER CREATED: "%s"', $params['TRANSACTIONID']));
        $comment->save();
    }

    protected function _createNotificationComment($comment)
    {
        $message = sprintf('[KliknPay Notification Processed] "%s"', $comment);
        $message = $this->_order->addStatusHistoryComment($message);
        $message->setIsCustomerNotified(null);
        return $message;
    }

    protected function setPaymentMethod()
    {
        $this->_paymentMethod = $this->_vplabKliknPay;
    }

    protected function _createTransaction($params)
    {
        $payment = $this->_order->getPayment();
        $payment->setLastTransId($params['NUMXKP']);
        $payment->setTransactionId($params['NUMXKP']);
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $params]
        );
        $formatedPrice = $this->_order->getBaseCurrency()->formatTxt(
            $params['MONTANTXKP']
        );
        $message = __('The authorized amount is %1.', $formatedPrice);

        // Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($params['NUMXKP'])
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
                $this->_invoiceSender->send($invoice);

                $this->_order->addStatusHistoryComment(__('You notified customer about invoice #%1.', $invoice->getId()))
                    ->setIsCustomerNotified(true)
                    ->save();
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
