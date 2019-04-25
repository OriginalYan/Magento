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

namespace Zitec\Dpd\Block\Adminhtml\Postcode\Update;

use Magento\Widget\Block\Adminhtml\Widget;
use Zitec\Dpd\Helper\Postcode\Search;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Files extends Widget
{

    /**
     * @var \Zitec\Dpd\Helper\Postcode\Search
     */
    protected $dpdPostcodeSearchHelper;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        Search $dpdPostcodeSearchHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->dpdPostcodeSearchHelper = $dpdPostcodeSearchHelper;
        $this->setTemplate('postcode/available-files.phtml');
    }

    /**
     * return all csv files in the predefined path
     * @return array
     */
    public function getAvailableCsvFiles()
    {
        $path = $this->dpdPostcodeSearchHelper->getPathToDatabaseUpgradeFiles();
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $files = [];
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $ext = pathinfo($entry, PATHINFO_EXTENSION);
                if (strtolower($ext) !== 'csv') {
                    continue;
                }
                $files[$entry] = filemtime($path . $entry);
            }
            closedir($handle);
        }
        asort($files);
        return $files;

    }
}


