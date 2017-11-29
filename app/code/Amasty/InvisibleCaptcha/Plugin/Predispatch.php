<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_InvisibleCaptcha
 */


namespace Amasty\InvisibleCaptcha\Plugin;

use Amasty\InvisibleCaptcha\Helper\Data;

class Predispatch
{
    /**
     * Google URl for checking captcha response
     */
    const GOOGLE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Action constructor.
     * @param \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory
     * @param Data $helper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory,
        Data $helper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function aroundDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $result = $proceed($request);
        if ($this->helper->isModuleOn()) {
            $logger = \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface');
            // $logger->debug('[reCAPTCHA] In CAPTCHA');
            // $logger->debug('[reCAPTCHA] URL: ' . $this->urlBuilder->getCurrentUrl());
            foreach ($this->helper->getCaptchaUrls() as $captchaUrl) {
                if (strpos($this->urlBuilder->getCurrentUrl(), $captchaUrl) !== false) {
                    $logger->debug('[reCAPTCHA] GOT URL: ' . $captchaUrl);
                    if ($request->isPost()) {
                        $token = $request->getPost('amasty_invisible_token');
                        $logger->debug('[reCAPTCHA] CHECK TOKEN: ' . $token);
                        $validation = $this->verifyCaptcha($token);
                        if (!$validation) {
                            $this->messageManager->addErrorMessage(__('Something is wrong'));

                            return $this->resultRedirectFactory->create()->setRefererUrl();
                        }
                    }
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $token
     * @return bool
     */
    protected function verifyCaptcha($token)
    {
        if ($token) {
            $curlParams = [
                'secret' => $this->helper->getConfigValueByPath(Data::CONFIG_PATH_GENERAL_SECRET_KEY),
                'response' => $token
            ];
            $this->curl->post(self::GOOGLE_VERIFY_URL, $curlParams);
            try {
                if (($this->curl->getStatus() == 200)
                    && array_key_exists('success', $answer = \Zend_Json::decode($this->curl->getBody()))
                ) {
                    return $answer['success'];
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
}
