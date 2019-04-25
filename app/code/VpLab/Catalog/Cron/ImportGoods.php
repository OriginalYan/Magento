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
        // return;  // 2018-09-06 Temp.stop

        $this->_url = 'ftp://' . $this->getConfig('ftp/host') . $this->getConfig('ftp/filename');

        // Load and parse XML from Remote Server
        $xml = $this->loadRemoteXml();
        if (!$xml) {
            $this->_logger->error('Error download or parse XML from: ' . $this->_url);
            return false;
        }

        // Select Quantities for products
        $qty = $this->collectQuantity($xml);
        print_r($qty);

        // Update products
        $this->updateProductQty($qty);
    }

    private function loadRemoteXml()
    {
        if (!trim($this->_url)) {
            $this->_logger->error('Empty or missed Remote URL');
            return false;
        }

        // get listining
        $p = curl_init();
        curl_setopt_array($p, [
            CURLOPT_TRANSFERTEXT => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->_url,
            CURLOPT_USERPWD => $this->getConfig('ftp/user') . ':' . $this->getConfig('ftp/password'),
        ]);
        $result = curl_exec($p);
        if (!trim($result)) {
            curl_close($p);
            return false;
        }
        $files = [];
        foreach (explode("\n", $result) as $row) {
            $parts = explode(" ", trim($row));
            $files[] = trim($parts[count($parts) - 1]);
        }
        if (!$files) {
            curl_close($p);
            return false;
        }
        // print_r($files);

        $filename = null;
        foreach ($files as $item) {
            if (preg_match('/ost_' . date('dmY') . '_/', $item)) {
                $filename = $item;
                break;
            }
        }
        $this->_logger->debug('Import goods from: ' . $filename);
        if (!$filename) {
            curl_close($p);
            return false;
        }

        $this->_url .= $filename;

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
        foreach ($xml->LINE as $item) {
            $sku = trim($item->ARTNAME);
            $cnt = $this->fixQty($item->QuantityOrdered);
            // print($sku . ' = ' . $cnt . "\n");
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
                print('SET ' . $cnt . ' for Product with SKU: ' . $sku . "\n");
                $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
                $stockItem->setQty($cnt);
                $stockItem->setIsInStock($cnt > 0);

                $this->_logger->debug('SET ' . $cnt . ' for Product with SKU: ' . $sku);
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
