<?php

include 'app/bootstrap.php';

use Magento\Framework\App\Bootstrap;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

try {
    $obj = $objectManager->create('VpLab\Catalog\Cron\ImportGoods');
    $obj->execute();

} catch (Exception $e) {
    // print_r($e);
    print "Error: " . $e->getMessage() . "\n";
}
