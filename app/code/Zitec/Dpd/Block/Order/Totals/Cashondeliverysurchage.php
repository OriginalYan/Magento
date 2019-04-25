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

namespace Zitec\Dpd\Block\Order\Totals;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Cashondeliverysurchage extends \Magento\Sales\Block\Order\Totals
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Tax\Model\Config $taxConfig,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->scopeConfig = $scopeConfig;
        $this->taxConfig = $taxConfig;
        $this->dpdHelper = $dpdHelper;
    }
    public function initTotals()
    {
        $title = $this->scopeConfig->getValue('payment/zitec_dpd_cashondelivery/total_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_getOrder()->getStoreId());

        if (!round($this->_getAmount(), 2)) {
            return;
        }

        $includingAfter = $this->_getAfter();
        if ($this->_displayBoth() || $this->_displayExcludingTax()) {
            $total = $this->dataObjectFactory->create([
                'data' => [
                    'code'       => 'zitec_dpd_cashondelivery_surcharge',
                    'value'      => $this->_getAmount(),
                    'base_value' => $this->_getBaseAmount(),
                    'label'      => $title . ($this->_displayBoth() ? ' ' . __('(Excl.Tax)') : ''),
                ]
            ]);
            $this->getParentBlock()->addTotal($total, $this->_getAfter());
            $includingAfter = 'zitec_dpd_cashondelivery_surcharge';
        }

        if ($this->_displayIncludingTax() || $this->_displayBoth()) {
            $total = $this->dataObjectFactory->create([
                'data' => [
                    'code'       => 'zitec_dpd_cashondelivery_surcharge_incl_tax',
                    'value'      => $this->_getAmount() + $this->_getTax(),
                    'base_value' => $this->_getBaseAmount() + $this->_getBaseTax(),
                    'label'      => $title . ($this->_displayBoth() ? ' ' . __('(Incl.Tax)') : ''),
                ]
            ]);
            $this->getParentBlock()->addTotal($total, $includingAfter);
        }
    }

    /**
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    protected function _getStore()
    {
        return $this->_getOrder()->getStore();
    }

    /**
     *
     * @return string
     */
    protected function _getAfter()
    {
        return $this->_displayBoth() ? 'shipping_incl' : 'shipping';
    }


    /**
     *
     * @return float
     */
    protected function _getAmount()
    {
        return $this->_getOrder()->getData('zitec_dpd_cashondelivery_surcharge');
    }

    /**
     *
     * @return float
     */
    protected function _getBaseAmount()
    {
        return $this->_getOrder()->getData('base_zitec_dpd_cashondelivery_surcharge');
    }

    /**
     *
     * @return float
     */
    protected function _getTax()
    {
        return $this->_getOrder()->getData('zitec_dpd_cashondelivery_surcharge_tax');
    }

    /**
     *
     * @return float
     */
    protected function _getBaseTax()
    {
        return $this->_getOrder()->getData('base_zitec_dpd_cashondelivery_surcharge_tax');
    }

    /**
     *
     * @return boolean
     */
    protected function _displayBoth()
    {
        return $this->_getConfig()->displaySalesShippingBoth($this->_getStore());
    }

    /**
     *
     * @return boolean
     */
    protected function _displayIncludingTax()
    {
        return $this->_getConfig()->displaySalesShippingInclTax($this->_getStore());
    }

    /**
     *
     * @return boolean
     */
    protected function _displayExcludingTax()
    {
        return $this->_getConfig()->displaySalesShippingExclTax($this->_getStore());
    }

    /**
     *
     * @return \Magento\Tax\Model\Config
     */
    protected function _getConfig()
    {
        return $this->taxConfig;
    }
}
