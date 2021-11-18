<?php

namespace Paynl\Payment\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\Storage\WriterInterface;

use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

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
        $this->configWriter->save('payment/paynl/order_description_prefix', 'Order ');

        $this->configWriter->save('payment/paynl/image_style', 'newest');
        $this->configWriter->save('payment/paynl/pay_style_checkout', 1);
        $this->configWriter->save('payment/paynl/icon_size', 'small');
        
        $setup->endSetup();
    }
}
