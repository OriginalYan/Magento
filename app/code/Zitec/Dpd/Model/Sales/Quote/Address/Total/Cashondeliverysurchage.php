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

namespace Zitec\Dpd\Model\Sales\Quote\Address\Total;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Cashondeliverysurchage extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    /**
     * @var \Zitec\Dpd\Helper\Payment
     */
    protected $dpdPaymentHelper;

    /**
     * @var \Zitec\Dpd\Model\Mysql4\Carrier\TablerateFactory
     */
    protected $dpdMysql4CarrierTablerateFactory;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryHelper;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    public function __construct(
        \Zitec\Dpd\Helper\Data $dpdHelper,
        \Zitec\Dpd\Helper\Payment $dpdPaymentHelper,
        \Zitec\Dpd\Model\Mysql4\Carrier\TablerateFactory $dpdMysql4CarrierTablerateFactory,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    )
    {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->dpdHelper = $dpdHelper;
        $this->dpdPaymentHelper = $dpdPaymentHelper;
        $this->dpdMysql4CarrierTablerateFactory = $dpdMysql4CarrierTablerateFactory;
        $this->directoryHelper = $directoryHelper;
        $this->taxCalculation = $taxCalculation;
        $this->taxConfig = $taxConfig;
        $this->taxHelper = $taxHelper;
        $this->scopeConfig = $scopeConfig;
        $this->setCode('zitec_dpd_cashondelivery_surcharge');
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $address = $shippingAssignment->getShipping()->getAddress();

        $address->setData('zitec_dpd_cashondelivery_surcharge', 0);
        $address->setData('base_zitec_dpd_cashondelivery_surcharge', 0);
        $address->setData('zitec_dpd_cashondelivery_surcharge_tax', 0);
        $address->setData('base_zitec_dpd_cashondelivery_surcharge_tax', 0);

        $paymentMethod = $quote->getPayment()->getMethod();

        if ($paymentMethod == $this->dpdHelper->getDpdPaymentCode() && $address->getAddressType() == 'shipping') {

            /* @var $quote Mage_Sales_Model_Quote */
            $shippingAddress = $quote->getShippingAddress();

            $request = $this->dataObjectFactory->create();
            $request->setWebsiteId($this->dpdPaymentHelper->getWebsiteId());
            $request->setDestCountryId($shippingAddress->getCountryId());
            $request->setDestRegionId($shippingAddress->getRegionId());
            $request->setDestPostcode($shippingAddress->getPostcode());
            $request->setPackageWeight($shippingAddress->getWeight());
            if ($this->taxHelper->shippingPriceIncludesTax($address->getQuote()->getStoreId())) {
                $request->setData('zitec_table_price', $shippingAddress->getBaseSubtotalInclTax());
            } else {
                $request->setData('zitec_table_price', $shippingAddress->getBaseSubtotal());
            }
            $request->setMethod(str_replace($this->dpdHelper->getDPDCarrierCode() . '_', '', $shippingAddress->getShippingMethod()));
            $tablerateSurcharge = $this->dpdMysql4CarrierTablerateFactory->create()->getCashOnDeliverySurcharge($request);

            if (is_null($tablerateSurcharge)) {
                return $this;
            } elseif (!empty($tablerateSurcharge)) {
                $baseCashondeliverySurcharge = $this->dpdHelper->calculateQuoteBaseCashOnDeliverySurcharge($quote, $tablerateSurcharge);
            } else {
                $baseCashondeliverySurcharge = $this->dpdHelper->returnDefaultBaseCashOnDeliverySurcharge($quote);
            }

            if (!isset($baseCashondeliverySurcharge)) {
                return $this;
            }

            $baseCurrencyCode        = $quote->getStore()->getBaseCurrencyCode();
            $currentCurrencyCode     = $quote->getStore()->getCurrentCurrencyCode();
            $cashondeliverySurcharge = $this->directoryHelper->currencyConvert($baseCashondeliverySurcharge, $baseCurrencyCode, $currentCurrencyCode);
            $address->setData('zitec_dpd_cashondelivery_surcharge', $cashondeliverySurcharge);
            $address->setData('base_zitec_dpd_cashondelivery_surcharge', $baseCashondeliverySurcharge);
            $this->_calculateSurchargeSalesTax($address);
            $quote->save();
        }

        $total->addTotalAmount('zitec_dpd_cashondelivery_surcharge', $address->getData('zitec_dpd_cashondelivery_surcharge'));
        $total->addBaseTotalAmount('base_zitec_dpd_cashondelivery_surcharge', $address->getData('base_zitec_dpd_cashondelivery_surcharge'));
        $total->addTotalAmount('zitec_dpd_cashondelivery_surcharge_tax', $address->getData('zitec_dpd_cashondelivery_surcharge_tax'));
        $total->addBaseTotalAmount('base_zitec_dpd_cashondelivery_surcharge_tax', $address->getData('base_zitec_dpd_cashondelivery_surcharge_tax'));

        return $this;
    }

    /**
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return
     */
    protected function _calculateSurchargeSalesTax(\Magento\Quote\Api\Data\AddressInterface $address)
    {
        $taxCalculator = $this->taxCalculation;
        /* @var $taxCalculator Mage_Tax_Model_Calculation */
        $customer = $address->getQuote()->getCustomer();
        if ($customer) {
            $taxCalculator->setCustomer($customer);
        }

        $store     = $address->getQuote()->getStore();
        $request   = $taxCalculator->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $address->getQuote()->getCustomerTaxClassId(),
            $store
        );
        $taxConfig = $this->taxConfig;
        /* @var $taxConfig Mage_Tax_Model_Config */
        $request->setProductClassId($taxConfig->getShippingTaxClass($store));

        $rate          = $taxCalculator->getRate($request);
        $inclTax       = $taxConfig->shippingPriceIncludesTax($store);
        $surcharge     = $address->getData('zitec_dpd_cashondelivery_surcharge');
        $baseSurcharge = $address->getData('base_zitec_dpd_cashondelivery_surcharge');

        // NOTA: Mira el comentario de 25 abr 2013 10:45 en #43 de collab.
        $surchargeTax     = $taxCalculator->calcTaxAmount($surcharge, $rate, $inclTax, true);
        $baseSurchargeTax = $taxCalculator->calcTaxAmount($baseSurcharge, $rate, $inclTax, true);

        $address->setExtraTaxAmount($address->getExtraTaxAmount() + $surchargeTax);
        $address->setBaseExtraTaxAmount($address->getBaseExtraTaxAmount() + $baseSurchargeTax);

        $address->setData('zitec_dpd_cashondelivery_surcharge_tax', $surchargeTax);
        $address->setData('base_zitec_dpd_cashondelivery_surcharge_tax', $baseSurchargeTax);

        if ($inclTax) {
            $address->setData('zitec_dpd_cashondelivery_surcharge', $surcharge - $surchargeTax);
            $address->setData('base_zitec_dpd_cashondelivery_surcharge', $baseSurcharge - $baseSurchargeTax);
        }
    }

    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $address = $quote->getShippingAddress();

        $amount = $address->getData('zitec_dpd_cashondelivery_surcharge');

        if ($amount != 0 && $address->getAddressType() == 'shipping') {
            $title = $this->scopeConfig->getValue('payment/zitec_dpd_cashondelivery/total_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $address->getQuote()->getStore());

            $address->addTotal(array(
                'code'  => $this->getCode(),
                'title' => $title,
                'value' => $amount
            ));

            return [
                'code' => $this->getCode(),
                'title' => $title,
                'value' => $amount,
            ];
        }

        return null;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        $title = $this->scopeConfig->getValue('payment/zitec_dpd_cashondelivery/total_title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $title;
    }
}
