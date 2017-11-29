<?php


namespace VpLab\CategoryAttribute\Setup;

use Magento\Framework\Module\Setup\Migration;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $categorySetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(CategorySetupFactory $categorySetupFactory)
    {
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var EavSetup $eavSetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
        $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'General');

        /**
         * Add attributes to the eav/attribute
         */

        $attributes = [
            'vp_annotation' => [
                'type' => 'text',
                'label' => 'Annotation',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 90,
                'wysiwyg_enabled' => false,
                'is_html_allowed_on_front' => false,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General',
            ]
        ];
        foreach ($attributes as $item => $data) {
            $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
        }
        foreach ($attributes as $item => $data) {
            $categorySetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $idg,
                $item,
                $data['sort_order']
            );
        }
        $setup->endSetup();
    }
}
