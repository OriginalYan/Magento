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

namespace Zitec\Dpd\Block\Postcode;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Autocompleter extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    protected $dpdHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Zitec\Dpd\Helper\Data $dpdHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->dpdHelper = $dpdHelper;
    }


    /**
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_showBlock()) {
            return $this->_getHtml($this->getFieldNames(), $this->getSections());
        } else {
            return '';
        }
    }
    /**
     *
     * @param array $fieldNames
     *
     * @return string
     */
    protected function _getHtml(array $fieldNames, array $sectionNames)
    {
        if (!$fieldNames || !$sectionNames) {
            return '';
        }
        $fieldsHtml = '';
        $fieldsHtml .= "
            fields['generic'] = [];
            fields['sections'].push('generic');
        ";
        foreach($sectionNames as $sectionName) {
            $fieldsHtml .= "
                fields['{$sectionName}'] = [];
                fields['sections'].push('{$sectionName}');
            ";
            foreach ($fieldNames as $fieldName) {
                $fieldsHtml .= "
                field = jQuery('#{$sectionName}:{$fieldName}');
                if (field) {
                    fields['{$sectionName}'].push(field);
                } else {
                    field = jQuery('#{$fieldName}');
                    if(field) {
                        fields['generic'].push(field);
                    }
                }";
            }

        }
        $loadingImageUrl =  $this->getSkinUrl('images/ajax-loader.gif');
        $loadingText = __('Loading');
        // TODO Zitec Set the country in the config file
        $country =  'RO';
        $url = '/zitec_dpd/shipment/validatePostcode';
        $html = "
<script type='text/javascript'>    
    require(['jquery', 'zitecDpdPostcodeAutocomplete'], function($, postcodeAutocomplete) {

        var fields = {'sections' : []};
        var field;

        {$fieldsHtml}
        var options = {
            loadingImageUrl: '{$loadingImageUrl}',
            loadingText: '{$loadingText}',
            country: '{$country}',
            url: '{$url}'
        };
        postcodeAutocomplete.initialize(fields, options);
    });
</script>";

        return $html;
    }

    /**
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array("street1", "street2", "city", "region_id", "country_id", 'street_1', 'street_2');
    }

    public function getSections() {
        return array('billing', 'shipping');
    }

    /**
     *
     * @return boolean
     */
    protected function _showBlock()
    {
        return $this->dpdHelper->moduleIsActive();
    }
}

