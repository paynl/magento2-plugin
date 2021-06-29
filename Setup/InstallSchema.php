<?php

namespace Paynl\Payment\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\Storage\WriterInterface;

use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * Class InstallSchema
 *
 * @package Paynl\Payment\Setup
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var  Store
     * */
    private $store;

    /**
     * @var  Logger
     * */
    private $logger;

    /**
     * Installschema constructor.
     *
     * @param WriterInterface $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(WriterInterface $configWriter, LoggerInterface $logger, Store $store)
    {
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->store = $store;
    }

    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->logger->debug('PAY.: Installing module.');
        $this->setDefaultValue('payment/paynl/order_description_prefix', 'Order: ');

        $setup->endSetup();
    }

    private function setDefaultValue($configName, $value)
    {
        if (strlen($this->store->getConfig($configName)) == 0) {
            if (strlen($value) > 0) {
                $this->configWriter->save($configName, $value);
            }
        }
    }
}
