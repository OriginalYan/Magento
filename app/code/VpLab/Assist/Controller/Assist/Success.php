<?php

namespace VpLab\Assist\Controller\Assist;

use Exception;

class Success extends \VpLab\Assist\Controller\Checkout
{
    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \VpLab\Assist\Model\Assist $paymentMethod,
        \VpLab\Assist\Helper\Checkout $checkoutHelper,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $quoteRepository, $orderFactory, $logger, $paymentMethod, $checkoutHelper, $cartManagement, $resultJsonFactory);

        $this->_transactionBuilder = $transactionBuilder;
        $this->_invoiceSender = $invoiceSender;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $this->_logger->debug('ASSIST SUCCESS: execute()');
        $this->_logger->debug("GET: " . print_r($_GET, true));
        $this->_logger->debug("Client: " . $this->_customerSession->getData('email', false));

        try {
            $params = $this->getRequest()->getParams();
            $result = $this->validateResponse($params);
            if (!$result) {
                throw new Exception('Error validate ASSIST response for Quote: "' . $params['tnx'] . '"');
            }
            $tnx = intval($params['tnx']);

            $this->_quote = $this->_getQuote($tnx);
            $this->_quote->setPaymentMethod($this->_paymentMethod->getCode());
            $this->_quote->getPayment()->importData(['method' => $this->_paymentMethod->getCode()]);
            $this->_quote->save();

            $this->initCheckout();
            try {
                $this->cartManagement->placeOrder($this->_quote->getId(), $this->_quote->getPayment());
                $this->_order = $this->_getOrder();
            } catch (\Exception $e) {
                $this->_order = $this->_getOrder($this->_quote->getReservedOrderId());
            }
            if (!$this->_order) {
                throw new Exception(sprintf('Could not locate order for Quote: "%s".', $tnx));
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

            if ($this->_order) {
                $this->getCheckoutSession()->setLastOrderId($this->_order->getId())
                            ->setLastRealOrderId($this->_order->getIncrementId())
                            ->setLastOrderStatus($this->_order->getStatus());
            }
            $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl('checkout/onepage/success'));

        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());

            $this->_cancelPayment();
            $this->_checkoutSession->restoreQuote();
            $this->messageManager->addExceptionMessage($e, __('Your payment failed, Please try again later'));
            // $this->messageManager->addError(__('Your payment failed, Please try again later'));
            $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl('checkout/onepage/failure'));
        }
    }

    protected function validateResponse($params)
    {
        if (!isset($params['tnx']) or !intval($params['tnx'])) {
            $this->_logger->error('Wrong response code from ASSIST for Quote: "' . (isset($params['tnx']) ? $params['tnx'] : '') . '".');
            return false;
        }
        $tnx = intval($params['tnx']);

        if (!isset($params['ordernumber']) or !trim($params['ordernumber']) or substr(trim($params['ordernumber']), 0, 1) != 'M') {
            $this->_logger->error('Wrong Order Number from ASSIST for Quote: "' . $tnx . '".');
            return false;
        }
        $order_num = intval(substr(trim($params['ordernumber']), 1));
        if ($tnx != $order_num) {
            $this->_logger->error('Transaction ID:' . $tnx . ' not equal Order ID:' . $order_num . ' (' . $params['ordernumber'] . ')');
            return false;
        }

        // TODO: Check order status on ASSIST Service
        if (!$this->checkTransactionStatus($tnx)) {
            $this->_logger->error('Wrong Order Status from ASSIST for Quote: "' . $tnx . '".');
            return false;
        }

        return true;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field)
    {
        $path = 'payment/vplab_assist/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Load quote by ID
     */
    protected function _getQuote($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId);
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

        $comment = $this->_createNotificationComment(sprintf('ORDER CREATED: "%s"', $params['billnumber']));
        $comment->save();
    }

    protected function _createTransaction($params)
    {
        $payment = $this->_order->getPayment();
        $payment->setLastTransId($params['billnumber']);
        $payment->setTransactionId($params['billnumber']);
        $payment->setAdditionalInformation(
            [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $params]
        );
        $formatedPrice = $this->_order->getBaseCurrency()->formatTxt(
            $this->_order->getGrandTotal()
        );
        $message = __('The authorized amount is %1.', $formatedPrice);

        // Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
            ->setOrder($this->_order)
            ->setTransactionId($params['billnumber'])
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

    protected function _createNotificationComment($comment)
    {
        $message = sprintf('[ASSIST Notification Processed] "%s"', $comment);
        $message = $this->_order->addStatusHistoryComment($message);
        $message->setIsCustomerNotified(null);
        return $message;
    }

    protected function checkTransactionStatus($order_id)
    {
        // FOR DEBUG !!!!
        return true;

        $data = [
            'Ordernumber' => $order_id,
            'Merchant_ID' => $this->getConfigData('merchant_id'),
            'Login' => $this->getConfigData('check_login'),
            'Password' => $this->getConfigData('check_password'),
            'Format' => 1
        ];
        $this->_logger->debug('CHECK: ' . print_r($data, true));

        $p = curl_init($this->getConfigData('check_url'));
        curl_setopt_array($p, [CURLOPT_HEADER => false,
                               CURLOPT_POST => true,
                               CURLOPT_RETURNTRANSFER => true,
                               CURLOPT_FOLLOWLOCATION => false,
                               CURLOPT_SSL_VERIFYPEER => false,
                               CURLOPT_VERBOSE => false,
                               CURLOPT_POSTFIELDS => $data,
        ]);
        $result = curl_exec($p);
        curl_close($p);

        $this->_logger->debug('CHECK RESULT: ' . print_r($result, true));

        $result = explode(';', trim($result));
        if (isset($result[12]) and trim($result[12]) == 'Approved') {
            return true;
        }
        return false;
    }
}
