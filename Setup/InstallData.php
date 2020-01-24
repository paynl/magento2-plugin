<?php


namespace Paynl\Payment\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;

class InstallData implements InstallDataInterface
{

  private $customerSetupFactory;

  /**
   * Constructor
   *
   * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
   */
  public function __construct(
    CustomerSetupFactory $customerSetupFactory
  ) {
    $this->customerSetupFactory = $customerSetupFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function install(
    ModuleDataSetupInterface $setup,
    ModuleContextInterface $context
  ) {
    $installer = $setup;
    mail('wouter@pay.nl','test install', 'magento2 instasll');
    $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

    $customerSetup->addAttribute('customer_address', 'rexample', [
      'label' => 'examplev',
      'input' => 'text',
      'type' => 'varchar',
      'source' => '',
      'required' => false,
      'position' => 333,
      'visible' => true,
      'system' => false,
      'is_used_in_grid' => false,
      'is_visible_in_grid' => false,
      'is_filterable_in_grid' => false,
      'is_searchable_in_grid' => false,
      'backend' => ''
    ]);


    $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'exampleu')
      ->addData(['used_in_forms' => [
        'customer_address_edit',
        'customer_register_address'
      ]]);
    $attribute->save();

    $installer->getConnection()->addColumn(
      $installer->getTable('quote_address'),
      'example',
      [
        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '255',
                'nullable' => false,
                'default' => null,
                'comment' => 'Custom Attribute',

      ]
    );

    $installer->getConnection()->addColumn(
      $installer->getTable('sales_order_address'),
      'example',
      [
        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
        'length' => '255',
        'nullable' => false,
        'default' => null,
        'comment' => 'Custom Attribute',
      ]
    );
  }


  


}