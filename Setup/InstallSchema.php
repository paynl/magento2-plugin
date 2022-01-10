<?php

namespace Paynl\Payment\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\Storage\WriterInterface;

use Magento\Store\Model\Store;
use \Paynl\Payment\Helper\PayHelper;

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
     * Installschema constructor.
     *
     * @param WriterInterface $configWriter
     */
    public function __construct(WriterInterface $configWriter, Store $store)
    {
        $this->configWriter = $configWriter;
        $this->store = $store;
    }

    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $setup->startSetup();

        payHelper::logDebug('Installing module.');
        $this->configWriter->save('payment/paynl/order_description_prefix', 'Order ');

        $this->configWriter->save('payment/paynl/image_style', 'newest');
        $this->configWriter->save('payment/paynl/pay_style_checkout', 1);
        $this->configWriter->save('payment/paynl/icon_size', 'small');
        
        $setup->endSetup();
    }
}
