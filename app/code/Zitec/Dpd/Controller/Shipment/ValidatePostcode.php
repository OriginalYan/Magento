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

namespace Zitec\Dpd\Controller\Shipment;

use Magento\Framework\App\Action\Action;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class ValidatePostcode extends Action
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
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    protected $dpdPostcodeSearchHelper;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Directory\Model\CountryFactory $directoryCountryFactory,
        \Magento\Directory\Model\RegionFactory $directoryRegionFactory,
        \Zitec\Dpd\Helper\Postcode\Search $dpdPostcodeSearchHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);

        $this->directoryCountryFactory = $directoryCountryFactory;
        $this->directoryRegionFactory = $directoryRegionFactory;
        $this->dpdPostcodeSearchHelper = $dpdPostcodeSearchHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }


    /**
     * this action is used to validate manually the address postcode
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (!isset($params['street'])) {
            if (array_key_exists('billing', $params)) {
                $params = $params['billing'];
            } else {
                if (array_key_exists('shipping', $params)) {
                    $params = $params['shipping'];
                }
            }
        }

        $resultJson = $this->resultJsonFactory->create();

        $address = '';
        foreach ($params['street'] as $street) {
            $address .= ' ' . $street;
        }

        $address = trim($address);
        $params['address'] = $address;
        if (!empty($params['country_id'])) {
            $countryName = $this->directoryCountryFactory->create()->loadByCode($params['country_id'])->getName();
            $params['country'] = $countryName;
        }
        if (!empty($params['region_id'])) {
            $regionName = $this->directoryRegionFactory->create()->load($params['region_id'])->getName();
            $params['region'] = $regionName;
        }

        $postcode = $this->dpdPostcodeSearchHelper->search($params);
        if (empty($postcode)) {
            $postcode = $this->dpdPostcodeSearchHelper->findAllSimilarAddressesForAddress($params);
        }

        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);

        return $resultJson->setData(['label' => $postcode, 'value' => $postcode]);
    }
}
