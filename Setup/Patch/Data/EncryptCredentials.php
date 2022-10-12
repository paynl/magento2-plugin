<?php

namespace Paynl\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use \Paynl\Payment\Helper\PayHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\Store;
use Magento\Store\Api\StoreRepositoryInterface;

class EncryptCredentials implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var  EncryptorInterface
     * */
    private $encryptor;

    /**
     * @var  Store
     * */
    private $store;

    /**
     * @var  StoreRepositoryInterface
     * */
    private $storeRepository;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, ConfigInterface $scopeConfig, EncryptorInterface $encryptor, Store $store, StoreRepositoryInterface $storeRepository)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->store = $store;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $stores = $this->storeRepository->getList();
        payHelper::log('Encrypting Credentials');

        // Default settings
        if (!empty($this->store->getConfig('payment/paynl/tokencode'))) {
            $this->scopeConfig->saveConfig('payment/paynl/tokencode_encrypted', $this->encryptor->encrypt($this->store->getConfig('payment/paynl/tokencode')));
            $this->scopeConfig->deleteConfig('payment/paynl/tokencode');
        }
        if (!empty($this->store->getConfig('payment/paynl/apitoken'))) {
            $this->scopeConfig->saveConfig('payment/paynl/apitoken_encrypted', $this->encryptor->encrypt($this->store->getConfig('payment/paynl/apitoken')));
            $this->scopeConfig->deleteConfig('payment/paynl/apitoken');
        }
        if (!empty($this->store->getConfig('payment/paynl/serviceid'))) {
            $this->scopeConfig->saveConfig('payment/paynl/serviceid_encrypted', $this->encryptor->encrypt($this->store->getConfig('payment/paynl/serviceid')));
            $this->scopeConfig->deleteConfig('payment/paynl/serviceid');
        }

        // MultiStore settings
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $websiteId = $store->getWebsiteId();
            if (!empty($store->getConfig('payment/paynl/tokencode'))) {
                $this->scopeConfig->saveConfig('payment/paynl/tokencode_encrypted', $this->encryptor->encrypt($store->getConfig('payment/paynl/tokencode')), 'store', $storeId);
                $this->scopeConfig->deleteConfig('payment/paynl/tokencode', 'store', $storeId);
                $this->scopeConfig->saveConfig('payment/paynl/tokencode_encrypted', $this->encryptor->encrypt($store->getConfig('payment/paynl/tokencode')), 'website', $websiteId);
                $this->scopeConfig->deleteConfig('payment/paynl/tokencode', 'website', $websiteId);
            }
            if (!empty($store->getConfig('payment/paynl/apitoken'))) {
                $this->scopeConfig->saveConfig('payment/paynl/apitoken_encrypted', $this->encryptor->encrypt($store->getConfig('payment/paynl/apitoken')), 'store', $storeId);
                $this->scopeConfig->deleteConfig('payment/paynl/apitoken', 'store', $storeId);
                $this->scopeConfig->saveConfig('payment/paynl/apitoken_encrypted', $this->encryptor->encrypt($store->getConfig('payment/paynl/apitoken')), 'website', $websiteId);
                $this->scopeConfig->deleteConfig('payment/paynl/apitoken', 'website', $websiteId);
            }
            if (!empty($store->getConfig('payment/paynl/serviceid'))) {
                $this->scopeConfig->saveConfig('payment/paynl/serviceid_encrypted', $this->encryptor->encrypt($store->getConfig('payment/paynl/serviceid')), 'store', $storeId);
                $this->scopeConfig->deleteConfig('payment/paynl/serviceid', 'store', $storeId);
                $this->scopeConfig->saveConfig('payment/paynl/serviceid_encrypted', $this->encryptor->encrypt($store->getConfig('payment/paynl/serviceid')), 'website', $websiteId);
                $this->scopeConfig->deleteConfig('payment/paynl/serviceid', 'website', $websiteId);
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
