<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Zitec_Dpd',
    __DIR__
);

$vendorPath = include VENDOR_PATH;

/** @var \Composer\Autoload\ClassLoader $composerAutoloader */
$composerAutoloader = include BP . DIRECTORY_SEPARATOR . $vendorPath . DIRECTORY_SEPARATOR . 'autoload.php';
if ($composerAutoloader instanceof \Composer\Autoload\ClassLoader) {
    $composerAutoloader->add('Zitec_Dpd_', __DIR__ . DIRECTORY_SEPARATOR . 'lib');
} else {
    throw new Exception('Could not register custom autoloader for Zitec_Dpd');
}
