<?php

namespace VpLab\KliknPay\Controller\KliknPay;

class Notify extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \VpLab\KliknPay\Model\Notification
     */
    protected $_notificationHandler;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \VpLab\KliknPay\Model\Notification $notificationHandler,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_logger = $logger;
        $this->_notificationHandler = $notificationHandler;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_logger->addDebug('KLIKNPAY NOTIFY: execute()');
        $this->_logger->addDebug("GET: " . print_r($_GET, true));

        try {
            $params = $this->getRequest()->getParams();
            $this->_notificationHandler->processNotification($params);

        } catch (\Exception $e) {
            $this->_logger->critical($e);
            // $this->getResponse()->setHttpResponseCode(500);
        }
    }
}
