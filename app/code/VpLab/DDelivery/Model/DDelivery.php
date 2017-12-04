<?php

namespace VpLab\DDelivery\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class DDelivery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    protected $_code = 'ddelivery';
    protected $_isFixed = false;
    protected $api_key;
    protected $packages = [
        '20x12x12' => ['h' => 20, 'w' => 12, 'l' => 12, 'v' => 2880],
        '21x21x15' => ['h' => 21, 'w' => 21, 'l' => 15, 'v' => 6615],
        '30x22x25' => ['h' => 30, 'w' => 22, 'l' => 25, 'v' => 16500],
        '45x30x30' => ['h' => 45, 'w' => 30, 'l' => 30, 'v' => 40500],
    ];

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_http;

    protected $_productRepository;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\HTTP\Client\Curl $http,
        array $data = []
        )
    {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_http = $http;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => $this->getConfigData('name')];
    }

    public function isCityRequired()
    {
        return true;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }
        $city = trim(mb_strtolower($request->getDestCity(), 'UTF-8'));
        if ($city == 'москва' or $city == 'moscow') {
            return false;
        }
        $this->_api_key = trim($this->getConfigData('api_key'));
        if (!$this->_api_key) {
            $this->_logger->error('[' . $this->getCarrierCode() . '] Invalid configuration: missed API KEY');
            return false;
        }

        $city_id = $this->getCityId($city);
        $this->_logger->debug('[' . $this->getCarrierCode() . '] CITY ID:' . $city_id);
        if (!$city_id) {
            return false;
        }

        list($weight, $volume) = $this->getOrderDimensions($request);
        $this->_logger->debug('[' . $this->getCarrierCode() . '] Weight: ' . $weight . ' Volume: ' . $volume);
        if (!$weight or !$volume) {
            return false;
        }

        $packages = $this->getPackageBoxes($volume);
        $this->_logger->debug('[' . $this->getCarrierCode() . '] PACKS: ' . print_r($packages, true));

        $result = $this->_rateResultFactory->create();

        foreach ($packages as $package) {
            $data = $this->requestRates($city_id, $weight, $package);
            // $this->_logger->debug(print_r($data, true));
            foreach ($data as $item) {
                if (isset($item->points)) {
                    foreach ($item->points as $pitem) {
                        $method = $this->_rateMethodFactory->create();

                        $method->setCarrier($this->getCarrierCode());
                        $method->setCarrierTitle($pitem->delivery_company_name . '. ' . $pitem->address);

                        $method->setMethod($item->type . '-' . $pitem->id);
                        $method->setMethodTitle($item->type_name);

                        $method->setPrice($pitem->price_delivery);
                        $method->setCost($pitem->price_delivery);

                        $result->append($method);
                    }
                } elseif (isset($item->delivery)) {
                    foreach ($item->delivery as $ditem) {
                        $method = $this->_rateMethodFactory->create();

                        $method->setCarrier($this->getCarrierCode());
                        $method->setCarrierTitle($ditem->delivery_company_name);

                        $method->setMethod($item->type . '-' . $ditem->delivery_company_id);
                        $method->setMethodTitle($item->type_name);

                        $method->setPrice($ditem->total_price);
                        $method->setCost($ditem->total_price);

                        $result->append($method);
                    }
                }
            }
        }

        return $result;
    }

    protected function getCityId($name)
    {
        $url = $this->getConfigData('api_url') . $this->_api_key . '/list/city.json?name=' . urlencode($name);
        // $this->_logger->debug('[' . $this->getCarrierCode() . '] ' . $url);

        $this->_http->get($url);
        if ($this->_http->getStatus() >= 300) {
            $this->_logger->error('[' . $this->getCarrierCode() . '] Error [' . $this->_http->getStatus() . '] fetching ' . $url);
            return;
        }
        try {
            $resp = $this->_http->getBody();
            // $this->_logger->debug($resp);
            $resp = json_decode($resp);
            // $this->_logger->debug(print_r($resp, true));
            if (count($resp) < 1) {
                return;
            }
            $item = $resp[0];
            $this->_logger->debug('[' . $this->getCarrierCode() . '] ID:' . $item->id . ' Name: ' . $item->name);

            return $item->id;

        } catch (\Exception $e) {
            $this->_logger->error('[' . $this->getCarrierCode() . '] Error parse JSON response from ' . $url . ' . ' . $e->getMessage());
            return;
        }
    }

    protected function getOrderDimensions($request)
    {
        $total_weight = 0;
        $total_volume = 0;
        $items = $request->getAllItems();
        foreach ($items as $item) {
            $product = $item->getProduct();
            if (!$product or !$product->getId()) {
                continue;
            }
            $product = $this->getProductRepository()->getById($product->getId());
            if (!$product or !$product->getId()) {
                continue;
            }
            $weight = $product->getData('weight');

            $volume = $product->getData('height') * $product->getData('length') * $product->getData('width');

            $total_weight += $weight;
            $total_volume += $volume;
        }
        return [$total_weight, $total_volume];
    }

    protected function getPackageBoxes($volume)
    {
        $packages = [];
        $v = $volume;
        while ($v > 0) {
            $last_item = null;
            $is_found = false;
            foreach ($this->packages as $k => $item) {
                if ($v <= $item['v']) {
                    $packages[] = $item;
                    $v -= $item['v'];
                    $is_found = true;
                    break;
                }
                $last_item = $item;
            }
            if (!$is_found) {
                $packages[] = $last_item;
                $v -= $last_item['v'];
            }
        }
        return $packages;
    }

    protected function requestRates($city_id, $weight, $package)
    {
        $url = $this->getConfigData('api_url') . $this->_api_key . '/calculator.json?';
        $url .= join('&', [
            'city_to=' . $city_id,
            'side1=' . $package['h'],
            'side2=' . $package['w'],
            'side3=' . $package['l'],
            'weight=' . $weight,
        ]);
        $this->_logger->debug('[' . $this->getCarrierCode() . '] ' . $url);

        $this->_http->get($url);
        if ($this->_http->getStatus() >= 300) {
            $this->_logger->error('[' . $this->getCarrierCode() . '] Error [' . $this->_http->getStatus() . '] fetching ' . $url);
            return;
        }
        try {
            $resp = $this->_http->getBody();
            // $this->_logger->debug($resp);
            $resp = json_decode($resp);
            // $this->_logger->debug(print_r($resp, true));
            if (!isset($resp->status) or $resp->status != 'ok' or !isset($resp->data)) {
                return;
            }
            return $resp->data;

        } catch (\Exception $e) {
            $this->_logger->error('[' . $this->getCarrierCode() . '] Error parse JSON response from ' . $url . ' . ' . $e->getMessage());
            return;
        }

    }

    protected function getProductRepository()
    {
        if ($this->_productRepository === null) {
            $this->_productRepository = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        }
        return $this->_productRepository;
    }
}
