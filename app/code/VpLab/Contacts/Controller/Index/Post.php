<?php

namespace VpLab\Contacts\Controller\Index;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class Post extends \Magento\Contact\Controller\Index\Post
{
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Post user question
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('*/*/');
            return;
        }

        $this->inlineTranslation->suspend();
        try {
            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($post);

            $logger = ObjectManager::getInstance()->get('Psr\Log\LoggerInterface');

            $error = false;

            if (!\Zend_Validate::is(trim($post['name']), 'NotEmpty')) {
                $error = true;
            }
            if (!\Zend_Validate::is(trim($post['comment']), 'NotEmpty')) {
                $error = true;
            }
            if (!\Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                $error = true;
            }
            if (\Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                $error = true;
            }
            if ($this->hasUrls(trim($post['comment']))) {
                $logger->debug('[CONTACTS] Post has URL in Comment');
                $error = true;
            }
            if ($error) {
                throw new \Exception();
            }

            // $logger->debug('[CONTACTS] Feedback message was send');

            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $recipient = $this->getRecipient($postObject['subject']);
            $logger->debug('[CONTACTS] Recipient: ' . $recipient);

            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE, $storeScope))
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars(['data' => $postObject])
                ->setFrom($this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER, $storeScope))
                ->addTo($this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope))
                ->setReplyTo($post['email'])
                ->getTransport();

            $transport->sendMessage();

            $logger->debug('[CONTACTS] Feedback message was send');

            $this->inlineTranslation->resume();
            $this->messageManager->addSuccess(
                __('Thanks for contacting us with your comments and questions. We\'ll respond to you very soon.')
            );
            $this->getDataPersistor()->clear('contact_us');
            $this->_redirect('contact/index');
            return;
        } catch (\Exception $e) {
            // $logger->debug($e->getMessage());
            $this->inlineTranslation->resume();
            $this->messageManager->addError(
                __('We can\'t process your request right now. Sorry, that\'s all we know.')
            );
            $this->getDataPersistor()->set('contact_us', $post);
            $this->_redirect('contact/index');
            return;
        }
    }

    protected function hasUrls($text)
    {
        $pattern = '/http[s]?:\/\/(?:[a-zA-Z]|[0-9]|[$-_@.&+]|[!*\(\),]|(?:%[0-9a-fA-F][0-9a-fA-F]))+/i';
        $validator = new \Zend_Validate_Regex(['pattern' => $pattern]);
        return $validator->isValid(trim($text));
    }

    /**
     * Get Data Persistor
     *
     * @return DataPersistorInterface
     */
    private function getDataPersistor()
    {
        if ($this->dataPersistor === null) {
            $this->dataPersistor = ObjectManager::getInstance()
                ->get(DataPersistorInterface::class);
        }

        return $this->dataPersistor;
    }

    protected function getRecipient($subject)
    {
        // $subject = trim(mb_strtolower($subject));
        // if ('order tracking' == $subject) {
        //     return 'store@vplab.com';
        // } elseif ('customer service' == $subject) {
        //     return 'store@vplab.com';
        // } elseif ('trade & export enquiries' == $subject) {
        //     return 'export@vplaboratory.com';
        // } elseif ('general enquiries' == $subject) {
        //     return 'info@vplab.com';
        // }
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);
    }
}
