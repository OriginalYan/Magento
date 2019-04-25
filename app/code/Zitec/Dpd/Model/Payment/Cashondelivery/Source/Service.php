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

namespace Zitec\Dpd\Model\Payment\Cashondelivery\Source;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Service implements\Magento\Framework\Option\ArrayInterface
{
    protected $_allowedServices = null;

    /**
     * @var \Zitec\Dpd\Model\Config\Source\ServiceFactory
     */
    protected $dpdConfigSourceServiceFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Zitec\Dpd\Model\Config\Source\ServiceFactory $dpdConfigSourceServiceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->dpdConfigSourceServiceFactory = $dpdConfigSourceServiceFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     *
     * @param boolean $isMultiselect
     *
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        $servicesSource = $this->dpdConfigSourceServiceFactory->create();
        $options = [];
        foreach ($servicesSource->getAvailableServices() as $serviceCode => $label) {
            if (in_array($serviceCode, $this->_getAllowedServices())) {
                $options[] = ['label' => $serviceCode . ' - ' . $label, 'value' => $serviceCode];
            }
        }

        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }

    /**
     *
     * @return array
     */
    protected function _getAllowedServices()
    {
        if (!is_array($this->_allowedServices)) {
            $this->_allowedServices = explode(",",
                $this->scopeConfig->getValue("payment/zitec_dpd_cashondelivery/services",
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        }

        return $this->_allowedServices;
    }


}



