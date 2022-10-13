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
     * @param ConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param Store $store
     * @param StoreRepositoryInterface $storeRepository
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
        $this->encryptConfig('payment/paynl/apitoken_encrypted', 'payment/paynl/apitoken', $this->store, 'default', 0);

        // MultiStore settings
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            // Stores
            $storeId = $store->getStoreId();
            $this->encryptConfig('payment/paynl/apitoken_encrypted', 'payment/paynl/apitoken', $store, 'store', $storeId);

            // Websites
            $websiteId = $store->getWebsiteId();
            $this->encryptConfig('payment/paynl/apitoken_encrypted', 'payment/paynl/apitoken', $store, 'website', $websiteId);
           }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @param string $path
     * @param string $pathOld
     * @param store $store
     * @param string $scope
     * @param int $scopeId
     */
    private function encryptConfig($path, $pathOld, $store, $scope, $scopeId)
    {
        try {
            if (!empty($store->getConfig($pathOld))) {
                $this->scopeConfig->saveConfig($path, $this->encryptor->encrypt($store->getConfig($pathOld)), $scope, $scopeId);
                $this->scopeConfig->deleteConfig($pathOld);
            }
        } catch (\Exception $e) {
            payHelper::log('Couldn\'t encrypt "' . $pathOld . '" - ' . $e->getMessage());
        }
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
