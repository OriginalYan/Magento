<?php

namespace VpLab\Catalog\Block\Product\View;

class GoogleTag extends \Magento\Framework\View\Element\Template
{
    const CAT_ALL_PRODUCTS = 41;

    protected $_product;
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $_categoryRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        $this->_coreRegistry = $registry;
        $this->_categoryRepository = $categoryRepository;

        parent::__construct($context);
    }

    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }

    /**
     * Returns full category path for product
     */
    public function getCategoryPath()
    {
        $cats = $this->getProduct()->getCategoryIds();
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
    protected function getCategoryPathForGA($category)
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

    public function getProductPriceForGA()
    {
        $priceRender = $this->getPriceRender();

        $price = 0;
        if ($priceRender) {
            try {
                $price = $priceRender->render(
                    \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                    $this->getProduct(),
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

    protected function getPriceRender()
    {
        return $this->getLayout()->getBlock('product.price.render.default');
    }
}
