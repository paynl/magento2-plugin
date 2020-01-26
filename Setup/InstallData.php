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

    $installer->getConnection()->addColumn(
      $installer->getTable('quote_address'),
      'paynl_cocnumber',
      [
        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
        'length' => '255',
        'nullable' => false,
        'default' => null,
        'comment' => 'paynl_cocnumber',
      ]
    );

    $installer->getConnection()->addColumn(
      $installer->getTable('quote_address'),
      'paynl_vatnumber',
      [
        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
        'length' => '255',
        'nullable' => false,
        'default' => null,
        'comment' => 'paynl_vatnumber',
      ]
    );

  }


  


}