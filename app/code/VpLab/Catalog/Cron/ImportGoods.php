<?php

namespace VpLab\Catalog\Cron;

use \Magento\Framework\Exception\NoSuchEntityException;

class ImportGoods
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $_stockRegistry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    private $_url;

    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_stockRegistry = $stockRegistry;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $this->_url = 'ftp://' . $this->getConfig('ftp/host') . $this->getConfig('ftp/filename');

        // Load and parse XML from Remote Server
        $xml = $this->loadRemoteXml();
        if (!$xml) {
            $this->_logger->error('Error download or parse XML from: ' . $this->_url);
            return false;
        }

        // Select Quantities for products
        $qty = $this->collectQuantity($xml);

        // Update products
        $this->updateProductQty($qty);
    }

    private function loadRemoteXml()
    {
        if (!trim($this->_url)) {
            $this->_logger->error('Empty or missed Remote URL');
            return false;
        }
        $p = curl_init();
        curl_setopt_array($p, [
            CURLOPT_TRANSFERTEXT => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->_url,
            CURLOPT_USERPWD => $this->getConfig('ftp/user') . ':' . $this->getConfig('ftp/password'),
        ]);
        $result = curl_exec($p);
        curl_close($p);

        $result = trim($result);
        if (!$result) {
            return false;
        }
        return simplexml_load_string($result);
    }

    private function collectQuantity($xml)
    {
        $result = [];
        foreach ($xml->Товар as $item) {
            $sku = trim($item->Артикул);
            $cnt = $this->fixQty($item->Остаток);
            if (isset($result[$sku])) {
                $this->_logger->info('Duplicated SKU: ' . $sku);
                continue;
            }
            $result[$sku] = $cnt;
        }
        return $result;
    }

    private function fixQty($value)
    {
        return intval(str_replace([' ', ' '], '', $value));
    }

    private function updateProductQty($qty)
    {
        foreach ($qty as $sku => $cnt) {
            try {
                $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
                $stockItem->setQty($cnt);
                $stockItem->setIsInStock($cnt > 0);

                // $this->_logger->debug('SET ' . $cnt . ' for Product with SKU: ' . $sku);
                $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);
            } catch (NoSuchEntityException $e) {
                $this->_logger->warning($e->getMessage());
            }
        }
    }

    protected function getConfig($path)
    {
        return $this->_scopeConfig->getValue('import_goods/' . $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
