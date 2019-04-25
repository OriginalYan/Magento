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

namespace Zitec\Dpd\Model\Mysql4\Dpd;

/**
 *
 * @category   Zitec
 * @package    Zitec_Dpd
 * @author     Zitec COM <magento@zitec.ro>
 */
class Manifest extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $connectionName
        );
    }



    protected function _construct()
    {
        $this->_init($this->getTable('zitec_dpd_manifest'), 'manifest_id');
    }

    /**
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Zitec\Dpd\Model\Mysql4\Dpd\Manifest
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $result = parent::_afterSave($object);

        $manifest = $object;
        /* @var $manifest \Zitec\Dpd\Model\Dpd\Manifest */
        foreach ($manifest->getShipsForManifest() as $ship) {
            /* @var $ship \Zitec\Dpd\Model\Dpd\Ship */
            $ship->setManifestId($manifest->getId());
            $ship->save();
        }

        return $result;
    }


}


