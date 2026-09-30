<?php

namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RenameCartebleue implements DataPatchInterface
{
    private const CONFIG_PATH = 'payment/paynl_payment_cartebleue/title';
    private const OLD_TITLE = 'Cartebleue';
    private const NEW_TITLE = 'Carte Bancaire';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('core_config_data');

        $connection->update(
            $tableName,
            ['value' => self::NEW_TITLE],
            [
                'path = ?' => self::CONFIG_PATH,
                'value = ?' => self::OLD_TITLE,
            ]
        );

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
