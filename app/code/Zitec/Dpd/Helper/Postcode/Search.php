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

namespace Zitec\Dpd\Helper\Postcode;

/**
 * this class is used for
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Search extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $directoryCountryFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $directoryRegionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Zitec_Dpd_Postcode_Search
     */
    protected $dpdSearchFactory;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Directory\Model\CountryFactory $directoryCountryFactory,
        \Magento\Directory\Model\RegionFactory $directoryRegionFactory,
        \Magento\Framework\Registry $registry,
        PostcodeSearchFactory $dpdSearchFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        parent::__construct(
            $context
        );

        $this->dpdSearchFactory = $dpdSearchFactory;
        $this->directoryCountryFactory = $directoryCountryFactory;
        $this->directoryRegionFactory = $directoryRegionFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->registry = $registry;
        $this->directoryList = $directoryList;
    }



    public function extractPostCodeForShippingRequest($request)
    {

        //TODO: move this method in a separate helper, to avoid getting a session object with object manager
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->create(\Magento\Checkout\Model\Session::class);


        $countryName = $this->directoryCountryFactory->create()->loadByCode($request->getDestCountryId())->getName();

        if ($this->isEnabledAutocompleteForPostcode($countryName)) {
            if($request->getDestRegionId()) {
                $regionName = $this->directoryRegionFactory->create()->load($request->getDestRegionId())->getName();
            }

            $address = array(
                'country'  => $countryName,
                'region'   => $regionName,
                'city'     => $request->getDestCity(),
                'address'  => $request->getDestStreet(),
                'postcode' => $request->getDestPostcode(),
            );

            $postcodeRelevance = new \stdClass();
            $postCode          = $this->search($address, $postcodeRelevance);

            $checkout    = $checkoutSession->getQuote();
            $shipAddress = $checkout->getShippingAddress();
            $shipAddress->setData('auto_postcode', $postCode);
            $shipAddress->setData('valid_auto_postcode', $this->isValid($postCode, $postcodeRelevance));
            if ($this->isValid($postCode, $postcodeRelevance)){
                $shipAddress->setPostcode($postCode);
            }
        } else {
            $postCode = $request->getDestPostcode();
        }

        return $postCode;
    }



    /**
     * it is used to create a list of relevant addresses for given address.
     * used in admin panel to validate the postcode
     *
     * @param array $address The content will be the edit form for address from admin
     * $address contain next keys
     *      MANDATORY
     *      country
     *      city
     *
     * OPTIONAL
     *      region
     *      address
     *      street
     */
    public function findAllSimilarAddressesForAddress($address){
        $countryName = 'Romania';
        if(!empty($address['country_id']) && empty($address['country'])){
            $countryName = $this->directoryCountryFactory->create()->loadByCode($address['country_id'])->getName();
            $address['country'] = $countryName;
        }

        if ($this->isEnabledAutocompleteForPostcode($countryName)) {
            if (!empty($address['region_id']) && empty($address['region'])) {
                $regionName        = $this->directoryRegionFactory->create()->load($address['region_id'])->getName();
                $address['region'] = $regionName;
            }
            $foundAddresses = $this->getSearchPostcodeModel()->searchSimilarAddresses($address);
            return $foundAddresses;
        }
        return false;
    }



    /**
     * @param array $address
     *      $address contain next keys
     *      MANDATORY
     *      country
     *      city
     *
     * OPTIONAL
     *      region
     *      address
     *      street
     *
     * @param null $postcodeRelevance
     *
     * @return string
     */
    public function search($address, $postcodeRelevance = null)
    {
        $foundPostCode = $this->getSearchPostcodeModel()->search($address, $postcodeRelevance);
        if (isset($address['postcode']) && strlen($address['postcode']) > 4) {
            if ($foundPostCode == $address['postcode']) {
                return $foundPostCode;
            } elseif (!empty($foundPostCode)) {
                //mark the response as not exactly the same
                return $foundPostCode;
            }

            return $address['postcode'];
        }

        return $foundPostCode;
    }

    /**
     * test if found postcode relevance is enough for considering the postcode useful in the rest of checkout process
     *
     * @param          $postCode
     * @param \stdClass $relevance
     *
     * @return int
     */
    public function isValid($postCode, \stdClass $relevance = null)
    {
        if (empty($relevance)) {
            return 0;
        }
        if (!empty($relevance->percent) && $relevance->percent > \Zitec_Dpd_Postcode_Search_Abstract::SEARCH_RESULT_RELEVANCE_THRESHOLD_FOR_VALIDATION) {
            return 1;
        }

        return 0;
    }


    public function isEnabledAutocompleteForPostcode($countryName)
    {
        $isValid = $this->getSearchPostcodeModel()->isEnabled($countryName);
        if(empty($isValid)){
            return false;
        }

        $value = $this->scopeConfig->getValue('carriers/zitecDpd/postcode_autocomplete_checkout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return !empty($value);
    }


    public function getSearchPostcodeModel()
    {
        $getSearchPostcodeModel = $this->registry->registry('getSearchPostcodeModel');

        if (empty($getSearchPostcodeModel)) {
            $libInstance = $this->dpdSearchFactory->create(\Zitec_Dpd_Postcode_Search::MYSQL_ADAPTER);
            $this->registry->register('getSearchPostcodeModel', $libInstance);
            $getSearchPostcodeModel = $libInstance;
        }

        return $getSearchPostcodeModel;
    }


    /**
     * return the path do database files CSV
     *
     * @return string
     */
    public function getPathToDatabaseUpgradeFiles(){
        return $this->directoryList->getPath('media') . '/dpd/postcode_updates/';
    }


    /**
     *
     * call the library function for postcode update
     *
     * @param $fileName
     *
     * @return bool
     * @throws \Exception
     */
    public function updateDatabase($fileName){
        $result = $this->getSearchPostcodeModel()->updateDatabase($fileName);
        if(empty($result)){
            throw new \Exception(__('An error occurred while updating postcode database. Please run again the import script. (A database backup is always created in zitec_dpd_postcodes_backup table.)'));
        }
        return true;
    }



}
