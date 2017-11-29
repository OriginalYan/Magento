<?php

namespace VpLab\YandexKassa\Controller\Yandex;

class Success extends \Magento\Checkout\Controller\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->_resultPageFactory = $resultPageFactory;

        parent::__construct($context, $customerSession, $customerRepository, $accountManagement);
    }

    public function execute()
    {
        $logger = $this->_objectManager->get('Psr\Log\LoggerInterface');

        $logger->addDebug('VpLab\YandexKassa\Controller\Yandex\Success::execute()');
        $logger->addDebug("GET: " . print_r($_GET, true));
        $logger->addDebug("Client: " . $this->_customerSession->getData('email', false));

        $session = $this->getOnepage()->getCheckout();

        $session->clearQuote();

        $resultPage = $this->_resultPageFactory->create();

        $this->_eventManager->dispatch('checkout_onepage_controller_success_action', ['order_ids' => [$session->getLastOrderId()]]);

        return $resultPage;
    }

    /**
     * Get onepage checkout model
     *
     * @return \Magento\Checkout\Model\Type\Onepage
     */
    public function getOnepage()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage');
    }
}
