<?php

namespace VpLab\Catalog\Helper;

class GoogleTags extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CAT_BY_GOAL = 61;
    const CAT_BY_SPORT = 62;
    const CAT_ALL_PRODUCTS = 41;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $_categoryRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_categoryRepository = $categoryRepository;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;

        parent::__construct($context);
    }

    public function getCurrentCategory()
    {
        return $this->_catalogLayer->getCurrentCategory();
    }

    public function getGAListName()
    {
        $category = $this->getCurrentCategory();
        $parents = $category->getParentCategories();
        if ($parents) {
            $upper_category = array_shift($parents);
        } else {
            $upper_category = $category;
        }
        if ($upper_category->getId() == self::CAT_BY_SPORT) {
            return 'By sport - ' . $category->getName();
        } elseif ($upper_category->getId() == self::CAT_BY_GOAL) {
            return 'By goal - ' . $category->getName();
        }
        return $category->getName() . ' - products';
    }

    /**
     * Returns full category path for product
     */
    public function getCategoryPath(\Magento\Catalog\Model\Product $product)
    {
        $cats = $product->getCategoryIds();
        if (!$cats) {
            return '';
        }
        $result = [];
        foreach ($cats as $cid) {
            $_category = $this->_categoryRepository->get($cid);
            $path = $this->getCategoryPathForGA($_category);
            if ($path and count($path) > count($result)) {
                $result = $path;
            }
        }
        return join(' - ', $result);
    }

    /**
     * Select all category paths for given $category for export to Google Analytics
     */
    protected function getCategoryPathForGA(\Magento\Catalog\Model\Category $category)
    {
        $parents = $category->getParentCategories();
        if (!$parents or !count($parents)) {
            return null;
        }
        $upper_category = array_shift($parents);
        if ($upper_category->getId() != self::CAT_ALL_PRODUCTS) {
            return null;
        }
        $result = [];
        foreach ($parents as $v) {
            $result[] = $v->getName();
        }
        return $result;
    }

    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getGAImpression($product, $price, $category_name, $list_name, $position)
    {
        return [
            'name' => $product->getName(),
            'id' => $product->getId(),
            'price' => $price,
            'category' => $category_name,
            'list' => $list_name,
            'position' => $position,
        ];
    }

    public function getGAProductClick($product, $price, $category_name, $list_name, $position)
    {
        return json_encode([
            'name' => $product->getName(),
            'id' => $product->getId(),
            'price' => $price,
            'cat' => $category_name,
            'list' => $list_name,
            'position' => $position,
            'url' => $product->getProductUrl(),
        ]);
    }

    public function getGACartAdd($product, $category_name)
    {
        return json_encode([
            'name' => $product->getName(),
            'id' => $product->getId(),
            'cat' => $category_name,
            'currency' => $this->getCurrencyCode(),
        ]);
    }

    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    public function getGAQuoteProducts()
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return null;
        }
        $items = $this->getQuoteItems($quote);
        if (!$items) {
            return null;
        }
        return json_encode($items);
    }

    public function getQuoteItems($quote)
    {
        $data = [];
        $parents = [];
        foreach ($quote->getItemsCollection() as $item) {
            $data[$item->getId()] = $item;
            $parents[] = $item->getParentItemId();
        }
        $result = [];
        foreach ($data as $k => $item) {
            if (in_array($k, $parents)) {
                continue;
            }
            if ($item->getParentItemId() and isset($data[$item->getParentItemId()])) {
                $p = $data[$item->getParentItemId()];
                $price = $p->getPrice();
            } else {
                $price = $item->getPrice();
            }
            $product = $item->getProduct();
            $category_name = $this->getCategoryPath($product);
            if (is_a($item, 'Magento\Sales\Model\Order\Item')) {
                $qty = $item->getQtyOrdered();
            } else {
                $qty = $item->getQty();
            }
            $result[] = [
                'name' => $product->getName(),
                'id' => $product->getId(),
                'price' => number_format($price, 2, '.', ''),
                'cat' => $category_name,
                'quantity' => $qty,
            ];
        }
        return $result;
    }
}
