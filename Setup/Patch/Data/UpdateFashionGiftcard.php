<?php

namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Config\Model\ResourceModel\Config;
use \Paynl\Payment\Helper\PayHelper;

class UpdateFashionGiftcard implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, ResourceConnection $resourceConnection, Config $resourceConfig)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        payHelper::logDebug('Apply patch: updateFashionGiftcard.');

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('core_config_data');

        $path = 'payment/paynl_payment_fashiongiftcard/payment_option_id';
        $query = "SELECT `value` FROM " . $tableName . " WHERE scope = 'default' AND `path`= '" . $path . "'";

        $result = $connection->fetchOne($query, ['path' => $path]);
        if (!$result) {
            return;
        }
        payHelper::logDebug('updateFashionGiftcard result ' . $result);
        if ($result == '1699') {
            # Update the incorrect profileid.
            $this->resourceConfig->saveConfig($path, '1669', 'default', 0);
        }

        $this->moduleDataSetup->endSetup();
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
