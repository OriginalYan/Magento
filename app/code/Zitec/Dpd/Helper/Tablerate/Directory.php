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

namespace Zitec\Dpd\Helper\Tablerate;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Directory extends \Magento\Directory\Helper\Data
{

    /**
     * Json representation of regions data
     *
     * @var string
     */
    protected $_regionJson2;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    protected $cache;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $directoryRegionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regCollectionFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Model\RegionFactory $directoryRegionFactory
    ) {
        parent::__construct($context, $configCacheType, $countryCollection, $regCollectionFactory, $jsonHelper, $storeManager, $currencyFactory);
        $this->directoryRegionFactory = $directoryRegionFactory;
    }
    public function getRegionJson2()
    {
        //TODO: do not use OM like this
        /** @var \Magento\Framework\App\ObjectManager $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var $cacheState \Magento\Framework\App\Cache\StateInterface */
        $cacheState = $om->get(\Magento\Framework\App\Cache\StateInterface::class);

        /** @var $magentoJsonHelper \Magento\Framework\Json\Helper\Data */
        $magentoJsonHelper = $om->get(\Magento\Framework\Json\Helper\Data::class);


        if (!$this->_regionJson2) {
            $cacheKey = 'CORE_DIRECTORY_REGIONS_JSON2_STORE' . $this->_storeManager->getStore()->getId();
            if ($cacheState->isEnabled('config')) {
                $json = $this->_configCacheType->load($cacheKey);
            }
            if (empty($json)) {
                $countryIds = array();
                foreach ($this->getCountryCollection() as $country) {
                    $countryIds[] = $country->getCountryId();
                }
                $collection = $this->directoryRegionFactory->create()->getResourceCollection()
                    ->addCountryFilter($countryIds)
                    ->load();

                $regions = array(
                    'config' => array(
                        'show_all_regions' => true,
                        'regions_required' => $magentoJsonHelper->jsonEncode(array()),
                    )
                );

                foreach ($collection as $region) {
                    if (!$region->getRegionId()) {
                        continue;
                    }
                    $regions[$region->getCountryId()][$region->getRegionId()] = array(
                        'code' => $region->getCode(),
                        'name' => __($region->getName())
                    );
                }
                $json = $magentoJsonHelper->jsonEncode($regions);
                if ($cacheState->isEnabled('config')) {
                    $this->_configCacheType->save($json, $cacheKey, array('config'));
                }
            }
            $this->_regionJson2 = $json;
        }

        return $this->_regionJson2;
    }
}
