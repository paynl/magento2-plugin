<?php

namespace Paynl\Payment\Setup;

use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpgradeData
 *
 * @package Paynl\Payment\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    private $logger;

    /**
     * UpgradeData constructor.
     *
     * @param SalesSetupFactory $salesSetupFactory
     * @param ResourceConnection $resourceConnection
     * @param Config $resourceConfig
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(SalesSetupFactory $salesSetupFactory, ResourceConnection $resourceConnection, Config $resourceConfig, WriterInterface $configWriter, StoreManagerInterface $storeManager, LoggerInterface $logger)
    {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->logger->debug('PAY.: Upgrade. Module version: ' . $context->getVersion());

        # Update fashiongiftcard when current install is lower then 2.0.1
        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            # Update fashiongiftcard profileid
            $this->updateFashionGiftcard();
        }

        $setup->endSetup();
    }

    private function updateFashionGiftcard()
    {
        $this->logger->debug('PAY.: updateFashionGiftcard');

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('core_config_data');

        $path = 'payment/paynl_payment_fashiongiftcard/payment_option_id';
        $query = "SELECT `value` FROM " . $tableName . " WHERE scope = 'default' AND `path`= '" . $path . "'";

        $result = $connection->fetchOne($query, ['path' => $path]);
        if (!$result) {
            return;
        }
        $this->logger->debug('PAY.: updateFashionGiftcard result ' . $result);
        if ($result == '1699') {
            # Update the incorrect profileid.
            $this->resourceConfig->saveConfig($path, '1669', 'default', 0);
        }
    }

}
