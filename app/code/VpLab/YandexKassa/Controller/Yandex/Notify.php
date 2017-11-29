<?php

namespace VpLab\YandexKassa\Controller\Yandex;

class Notify extends \Magento\Framework\App\Action\Action
{
    const NOTIFY_ACTION = 'paymentAviso';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \VpLab\YandexKassa\Model\Notification
     */
    protected $_notificationHandler;

    /**
     * @var \VpLab\YandexKassa\Model\YandexKassa
     */
    protected $_paymentMethod;

    /**
     * @var \VpLab\YandexKassa\Helper\Checkout
     */
    protected $_checkoutHelper;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \VpLab\YandexKassa\Model\Notification $notificationHandler,
        \VpLab\YandexKassa\Model\YandexKassa $paymentMethod,
        \Psr\Log\LoggerInterface $logger,
        \VpLab\YandexKassa\Helper\Checkout $checkoutHelper
    ) {
        $this->_logger = $logger;
        $this->_notificationHandler = $notificationHandler;
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutHelper = $checkoutHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->_logger->addDebug('VpLab\YandexKassa\Controller\Yandex\Notify::execute()');
        $this->_logger->addDebug("POST: " . print_r($_POST, true));

        try {
            $params = $this->getRequest()->getParams();
            $invoice_id = isset($params['invoiceId']) ? trim($params['invoiceId']) : 0;
            $action = isset($params['action']) ? trim($params['action']) : '';
            if ($action != self::NOTIFY_ACTION) {
                $this->_logger->addDebug('[YANDEX] Wrong action. Expected paymentAviso, but got ' . (isset($params['action']) ? trim($params['action']) : ''), 'payments');
                return $this->_checkoutHelper->makeResponse(self::NOTIFY_ACTION, $invoice_id, 200);
            }
            if (!$this->_paymentMethod->validateResponse($params)) {
                return $this->_checkoutHelper->makeResponse(self::NOTIFY_ACTION, $invoice_id, 1);
            }

            $this->_notificationHandler->processNotification($params);

            return $this->_checkoutHelper->makeResponse(self::NOTIFY_ACTION, $invoice_id, 0);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return $this->_checkoutHelper->makeResponse(self::NOTIFY_ACTION, $invoice_id, 200);
        }
    }
}
