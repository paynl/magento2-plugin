<?php

namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use \Paynl\Payment\Helper\PayHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;

class EncryptCredentials implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     *
     * @var ScopeConfigInterface;
     */
    protected $scopeConfig;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var  EncryptorInterface
     * */
    private $encryptor;

    /**
     * @var  ResourceConnection
     * */
    private $resource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor    
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, ScopeConfigInterface $scopeConfig, ConfigInterface $config, EncryptorInterface $encryptor, ResourceConnection $resource)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        payHelper::log('Encrypting Credentials');
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('core_config_data');

        $select = $connection->select()->from([$tableName])->where('path = ?', 'payment/paynl/apitoken');
        $results = $connection->fetchAll($select);
        if (!empty($results)) {
            foreach ($results as $result) {
                try {
                    $config = $this->scopeConfig->getValue('payment/paynl/apitoken', $result['scope'], $result['scope_id']);
                    if (!empty($config)) {
                        $this->config->saveConfig('payment/paynl/apitoken_encrypted', $this->encryptor->encrypt($config), $result['scope'], $result['scope_id']);
                        $this->config->deleteConfig('payment/paynl/apitoken', $result['scope'], $result['scope_id']);
                    }
                } catch (\Exception $e) {
                    payHelper::log('Couldn\'t encrypt \'payment/paynl/apitoken\' - ' . $e->getMessage());
                }
            }
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
