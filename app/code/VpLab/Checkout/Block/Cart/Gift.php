<?php

namespace VpLab\Checkout\Block\Cart;

class Gift extends \Magento\Checkout\Block\Cart\AbstractCart
{
    public static $GIFT_MIN_AMOUNT = 2500;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    public function getSubTotal()
    {
        $quote = $this->getQuote();
        return $quote->getSubtotal();
    }

    public function getGiftProducts()
    {
        $collection = $this->_productCollectionFactory->create()
            ->addAttributeToFilter('is_gift', ['eq' => 1])
            ->addAttributeToFilter('status', ['eq' => 1])
            ->joinField('stock_item', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id', 'is_in_stock=1')
            ->setOrder('name', 'ASC');
        // $collection->printLogQuery(true);
        return $collection;
    }

    public function isGiftEnable()
    {
        $subtotal = $this->getSubTotal();
        if ($subtotal < self::$GIFT_MIN_AMOUNT) {
            return false;
        }
        if ($this->isCartHasGift()) {
            return false;
        }
        return true;
    }

    protected function isCartHasGift()
    {
        foreach ($this->getQuote()->getItems() as $quoteItem) {
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

    protected function isGiftProduct($pk)
    {
        $product = $this->_productRepository->getById($pk);
        if (!$product) {
            return false;
        }
        $is_gift = $product->getData('is_gift');
        return $is_gift;
    }
}
