<?php

use Magento\Framework\Logger\Monolog;

class Zitec_Dpd_Api_Logger_Magento extends Zitec_Dpd_Api_Logger_Abstract
{
    public function log($_message)
    {
        $objectManager = Magento\Framework\App\ObjectManager::getInstance();
        /** @var Monolog $logger */
        $logger = $objectManager->create(Monolog::class);

        $logger->log(Monolog::INFO, $_message);
    }
}
