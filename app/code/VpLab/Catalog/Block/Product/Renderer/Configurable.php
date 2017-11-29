<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace VpLab\Catalog\Block\Product\Renderer;

// use Magento\Catalog\Block\Product\Context;
// use Magento\Catalog\Helper\Product as CatalogProduct;
// use Magento\ConfigurableProduct\Helper\Data;
// use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
// use Magento\Customer\Helper\Session\CurrentCustomer;
// use Magento\Framework\Json\EncoderInterface;
// use Magento\Framework\Pricing\PriceCurrencyInterface;
// use Magento\Catalog\Model\Product;
// use Magento\Framework\Stdlib\ArrayUtils;
// use Magento\Store\Model\ScopeInterface;
// use Magento\Swatches\Helper\Data as SwatchData;
// use Magento\Swatches\Helper\Media;
// use Magento\Swatches\Model\Swatch;

/**
 * Swatch renderer block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable implements
    \Magento\Framework\DataObject\IdentityInterface
{
    protected function _getAdditionalConfig()
    {
        $sku = [];
        foreach ($this->getAllowProducts() as $product) {
            $sku[$product->getId()] = $product->getSku();
        }
        return ['sku' => $sku];
    }
}
