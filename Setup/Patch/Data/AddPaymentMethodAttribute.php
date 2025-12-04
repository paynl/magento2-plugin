<?php
namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Setup\EavSetupFactory;

class AddPaymentMethodAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private $moduleDataSetup;
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        
        $setup->startSetup();
        
        $eavSetup->addAttribute(Product::ENTITY, 'paynl_product_allowed_payment_methods', [
            'type' => 'text',
            'label' => 'Allowed Pay. Payment Methods',
            'input' => 'multiselect',
            'source' => 'Paynl\Payment\Model\Attribute\Source\PaymentMethods',
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'General',
            'used_in_product_listing' => false,
        ]);

        $setup->endSetup();
    }

    public function revert()
    {
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }

}
