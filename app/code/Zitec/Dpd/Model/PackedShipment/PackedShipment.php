<?php
/**
 * Zitec_Dpd – shipping carrier extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @copyright  Copyright (c) 2014 Zitec COM
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Zitec\Dpd\Model\PackedShipment;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class PackedShipment
{
    protected $_packages = array();

    /**
     * @var \Zitec\Dpd\Model\PackedShipment\PackageFactory
     */
    protected $packedShipmentPackageFactory;

    public function __construct(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $packages,
        \Zitec\Dpd\Model\PackedShipment\PackageFactory $packedShipmentPackageFactory
    )
    {
        $this->packedShipmentPackageFactory = $packedShipmentPackageFactory;
        if (!is_array($packages)) {
            return;
        }

        foreach ($packages as $packageData) {
            //to avoid errors check that each package has products
            if (!empty($packageData['ids'])) {
                $package           = $this->packedShipmentPackageFactory->create($shipment, $packageData['ids'], $packageData['ref']);
                $this->_packages[] = $package;
            }
        };
    }

    /**
     *
     * @return array
     */
    public function getPackages()
    {
        return $this->_packages;
    }
}
