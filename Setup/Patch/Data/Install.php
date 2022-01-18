<?php

namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\Store;
use \Paynl\Payment\Helper\PayHelper;

class Install implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var  Store
     * */
    private $store;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, WriterInterface $configWriter, Store $store)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->store = $store;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        if (empty($this->store->getConfig('payment/paynl/apitoken')) && empty($this->store->getConfig('payment/paynl/serviceid'))) {
            payHelper::logDebug('Installing module.');
            $this->configWriter->save('payment/paynl/order_description_prefix', 'Order ');
            $this->configWriter->save('payment/paynl/image_style', 'newest');
            $this->configWriter->save('payment/paynl/pay_style_checkout', 1);
            $this->configWriter->save('payment/paynl/icon_size', 'small');
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
