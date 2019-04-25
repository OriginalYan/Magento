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

namespace Zitec\Dpd\Model\Sales\Order\Pdf\Total;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Cashondeliverysurchage extends \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal
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

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Tax\Model\Config $taxConfig,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->taxConfig = $taxConfig;
        $this->dpdHelper = $dpdHelper;
    }
    public function getTotalsForDisplay()
    {
        $amount = $this->getOrder()->getData('zitec_dpd_cashondelivery_surcharge');
        if (floatval($amount)) {
            if ($this->getAmountPrefix()) {
                $discount = $this->getAmountPrefix() . $discount;
            }

            $title    = $this->scopeConfig->getValue('payment/zitec_dpd_cashondelivery/total_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getOrder()->getStoreId());
            $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

            $totals = array();
            if ($this->_displayBoth() || $this->_displayExcludingTax()) {
                $totals[] = array(
                    'label'     => $title . ($this->_displayBoth() ? __(' (Excl. Tax)') : '') . ':',
                    'amount'    => $this->getOrder()->formatPriceTxt($amount),
                    'font_size' => $fontSize,
                );
            }

            if ($this->_displayBoth() || $this->_displayIncludingTax()) {
                $amount += $this->getOrder()->getData('zitec_dpd_cashondelivery_surcharge_tax');
                $totals[] = array(
                    'label'     => $title . ($this->_displayBoth() ? __(' (Incl. Tax)') : '') . ':',
                    'amount'    => $this->getOrder()->formatPriceTxt($amount),
                    'font_size' => $fontSize,
                );
            }

            return $totals;
        }
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

    /**
     *
     * @return \Magento\Store\Model\Store
     */
    protected function _getStore()
    {
        return $this->getOrder()->getStore();
    }

}
