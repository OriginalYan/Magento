<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace VpLab\Catalog\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Product list
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    const PRICE_DISPLAY_LABEL = 'from&nbsp;';
    const CAT_BY_GOAL = 61;
    const CAT_BY_SPORT = 62;
    const CAT_ALL_PRODUCTS = 41;

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductPrice(\Magento\Catalog\Model\Product $product)
    {
        $priceRender = $this->getPriceRender();

        $price = '';
        if ($priceRender) {
            $displayLabel = $this->getDisplayLabel($product);

            try {
                $price = $priceRender->render(
                    \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                    $product,
                    [
                        'include_container' => true,
                        'display_minimal_price' => true,
                        'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                        'list_category_page' => true,
                        'display_label' => $displayLabel,
                    ]
                );
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                // var_dump($e);
            }
        }

        return $price;
    }

    protected function getDisplayLabel($product)
    {
        $productType = $product->getTypeId();
        if ($productType != 'configurable') {
            return null;
        }
        $attributes = $product->getTypeInstance()->getUsedProductAttributes($product);
        if (!$attributes) {
            return null;
        }
        foreach ($attributes as $attribute) {
            if ($attribute->getName() == 'package') {
                return __('from') . '&nbsp;';
                // return self::PRICE_DISPLAY_LABEL;
            }
        }
        return null;
    }

    public function getCurrentCategory()
    {
        return $this->getLayer()->getCurrentCategory();
    }

    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getProductPriceForGA(\Magento\Catalog\Model\Product $product)
    {
        $priceRender = $this->getPriceRender();

        $price = 0;
        if ($priceRender) {
            try {
                $price = $priceRender->render(
                    \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                    $product,
                    [
                        'include_container' => false,
                        'display_minimal_price' => true,
                        'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                        'list_category_page' => true,
                        'display_label' => '',
                    ]
                );
                if (preg_match('/<span class="price">([^<]+?)<\/span>/', $price, $match)) {
                    $price = preg_replace('/[^\d\.]/', '', trim($match[0]));
                } else {
                    $price = 0;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                // var_dump($e);
            }
        }
        return $price;
    }
}
