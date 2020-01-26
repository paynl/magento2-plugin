<?php

namespace Paynl\Payment\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
  public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
  {
    $installer = $setup;

    $installer->startSetup();
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

    $installer->endSetup();

  }
}