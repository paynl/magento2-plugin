<?php

namespace Paynl\Payment\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class RemoveIndexFromPayProcessing implements SchemaPatchInterface
{
    private $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public function apply()
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('pay_processing');

        if ($connection->isTableExists($tableName)) {
            $indexName = 'PAY_PROCESSING_PAYORDERID';

            $indexes = $connection->getIndexList($tableName);
            if (isset($indexes[$indexName])) {
                $connection->query("ALTER TABLE $tableName DROP INDEX $indexName");
            }
        }

        $this->schemaSetup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}

