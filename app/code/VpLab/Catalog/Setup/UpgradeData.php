<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace VpLab\Catalog\Setup;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    private $categorySetupFactory;
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(CategorySetupFactory $categorySetupFactory, EavSetupFactory $eavSetupFactory)
    {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(\Magento\Framework\Setup\ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /**
         * Add attributes to the eav/attribute
         */
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'vp_annotation',
            [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Annotation',
                'required' => false,
                'sort_order' => 10,
                'user_defined' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
            ]
        );

        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $categorySetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'vp_annotation',
            [
                'type' => 'text',
                'input' => 'textarea',
                'label' => 'Annotation',
                'required' => false,
                'sort_order' => 10,
                'user_defined' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
            ]
        );

        $setup->endSetup();
    }
}
