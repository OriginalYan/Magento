<?php

namespace Vplab\Checkout\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;

class GiftQtyCheckerPlugin
{
    public static $GIFT_MIN_AMOUNT = 30;

    protected $_productRepository;

    /**
     * Don't allow add one more Gift
     */
    public function beforeAddProduct(\Magento\Checkout\Model\Cart $cart, $productInfo, $requestInfo = null)
    {
        $product = $this->getProduct($productInfo);
        if (!$product) {
            return [$productInfo, $requestInfo];
        }
        $is_gift = $product->getData('is_gift');
        if (!$is_gift) {
            return [$productInfo, $requestInfo];
        }
        if ($this->isCartHasGift($cart)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('You can select only one gift.'));
        }
        return [$productInfo, $requestInfo];
    }

    /**
     * Control guantity of Gift on the cart
     */
    public function beforeSuggestItemsQty(\Magento\Checkout\Model\Cart $cart, $data)
    {
        $allow_gift = $this->isGiftEnable($cart);

        $has_gift = false;
        foreach ($data as $itemId => $itemInfo) {
            if (!isset($itemInfo['qty'])) {
                continue;
            }
            $qty = (float)$itemInfo['qty'];
            if ($qty <= 0) {
                continue;
            }
            $quoteItem = $cart->getQuote()->getItemById($itemId);
            if (!$quoteItem) {
                continue;
            }
            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }
            $is_gift = $this->isGiftProduct($product->getId());
            if ($is_gift) {
                if (!$allow_gift) {
                    $data[$itemId]['qty'] = 0;
                } elseif ($has_gift) {
                    // Only one Gift can be on Cart
                    $data[$itemId]['qty'] = 0;
                } elseif ($qty > 1) {
                    // Only one instance of Gift can be in Cart
                    $data[$itemId]['qty'] = 1;
                }
                $has_gift = true;
            }
        }
        return [$data];
    }

    public function afterRemoveItem(\Magento\Checkout\Model\Cart $cart, $result)
    {
        $result->getQuote()->collectTotals();
        $allow_gift = $this->isGiftEnable($result);

        if ($allow_gift) {
            return $result;
        }
        foreach ($result->getItems() as $quoteItem) {
            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }
            $is_gift = $this->isGiftProduct($product->getId());
            if (!$is_gift) {
                continue;
            }
            $result->getQuote()->removeItem($quoteItem->getId());
        }
        return $result;
    }

    protected function getProductRepository()
    {
        if ($this->_productRepository === null) {
            $this->_productRepository = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        }
        return $this->_productRepository;
    }

    protected function isGiftProduct($pk)
    {
        $product = $this->getProductRepository()->getById($pk);
        if (!$product) {
            return false;
        }
        $is_gift = $product->getData('is_gift');
        return $is_gift;
    }

    protected function getProduct($productInfo)
    {
        $product = null;
        if ($productInfo instanceof Product) {
            $product = $productInfo;
            if (!$product->getId()) {
                return null;
            }
        } elseif (is_int($productInfo) || is_string($productInfo)) {
            try {
                return $this->getProductRepository()->getById($productInfo);
            } catch (NoSuchEntityException $e) {
                // pass
            }
        }
        return $product;
    }

    protected function isCartHasGift($cart)
    {
        foreach ($cart->getItems() as $quoteItem) {
            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }
            $is_gift = $this->isGiftProduct($product->getId());
            if ($is_gift) {
                return true;
            }
        }
        return false;
    }

    protected function isGiftEnable($cart)
    {
        $subtotal = $cart->getQuote()->getSubtotal();
        if ($subtotal < self::$GIFT_MIN_AMOUNT) {
            return false;
        }
        return true;
    }
}
