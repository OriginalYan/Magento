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

namespace Zitec\Dpd\Model\Config\Data;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class ConfigDataAbstract extends \Magento\Framework\App\Config\Value
{

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Zitec\Dpd\Helper\Ws
     */
    protected $dpdWsHelper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Helper\Ws $dpdWsHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dpdHelper = $dpdHelper;
        $this->dpdWsHelper = $dpdWsHelper;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     *
     * @return boolean
     */
    protected function _isActive()
    {
        $isActive = $this->_getConfigValue('active');

        return $isActive ? true : false;
    }

    /**
     *
     * @param string $field
     * @param string $group
     *
     * @return string|null
     */
    protected function _getConfigValue($field, $group = 'zitec_dpd')
    {
        $configValueData = $this->getData("groups/{$group}/fields/{$field}");
        if (is_array($configValueData) && array_key_exists('value', $configValueData)) {
            $configValue = $configValueData['value'];

            return $configValue;
        }

        return null;
    }
}


