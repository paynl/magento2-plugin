<?php

namespace Paynl\Payment\Setup;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\State;

use \Paynl\Paymentmethods;
use \Paynl\Payment\Model\Config as PayConfig;
use \Paynl\Payment\Model\ConfigProvider;

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

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var  Store
     * */
    private $store;

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
    public function __construct(SalesSetupFactory $salesSetupFactory, ResourceConnection $resourceConnection, Config $resourceConfig, WriterInterface $configWriter, StoreManagerInterface $storeManager, LoggerInterface $logger, ScopeConfigInterface $scopeConfig, RequestInterface $request, Store $store, State $state)
    {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;  
        $this->_request = $request;
        $this->store = $store;
        $this->state = $state;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);          
        $this->logger->debug('PAY.: Upgrade. Module version: ' . $context->getVersion());

        # Update fashiongiftcard when current install is lower then 2.0.1
        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            # Update fashiongiftcard profileid
            $this->updateFashionGiftcard();
        }
        $this->setDefaults();

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

    private function setDefaults()
    {
        //Get all PAY. methods
        $this->logger->debug('PAY.: getting methods');
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $paymentHelper = $objectManager->get('Magento\Payment\Helper\Data');
        $paymentMethodList = $paymentHelper->getPaymentMethods();
        $pay_methods = array();
        foreach ($paymentMethodList as $key => $value) {
            if (strpos($key, 'paynl_') !== false && $key != 'paynl_payment_paylink') {
                $pay_methods[$key] = $value;
            }
        }

        //Check for missing defaults first
        $this->logger->debug('PAY.: checking defaults');
        $missing_defaults = array();
        foreach ($pay_methods as $key => $value) {
            if (
                strlen($this->store->getConfig('payment/' . $key . '/min_order_total')) == 0 ||
                strlen($this->store->getConfig('payment/' . $key . '/max_order_total')) == 0 ||
                strlen($this->store->getConfig('payment/' . $key . '/instructions')) == 0 ||
                strlen($this->store->getConfig('payment/' . $key . '/brand_id')) == 0
            ) {
                $missing_defaults[$key] = $value;
            }
        }

        if (!empty($missing_defaults)) {
            //Make sure PAY. is configured
            $configured = $this->configureSDK();
            $this->logger->debug('PAY.: configureSDK (' . $configured . ')');
            if ($configured) {
                $list = Paymentmethods::getList();
                //Set all missing defaults
                foreach ($missing_defaults as $key => $value) {
                    $paymentOptionId = $value['payment_option_id'];
                    $this->logger->debug('PAY.: setting defaults for (' . $key . ')');
                    if (isset($list[$paymentOptionId])) {
                        $method = $list[$paymentOptionId];
                        if (isset($method['min_amount'])) {
                            $this->setDefaultValue('payment/' . $key . '/min_order_total', floatval($method['min_amount'] / 100));
                        }
                        if (isset($method['max_amount'])) {
                            $this->setDefaultValue('payment/' . $key . '/max_order_total', floatval($method['max_amount'] / 100));
                        }
                        if (isset($method['brand']['public_description'])) {
                            $this->setDefaultValue('payment/' . $key . '/instructions', $method['brand']['public_description']);
                        }
                        if (isset($method['brand']['id'])) {
                            $this->setDefaultValue('payment/' . $key . '/brand_id', $method['brand']['id']);
                        }
                    }
                }
            }
        }

        return;
    }

    protected function configureSDK()
    {
        $apiToken = trim($this->getConfigValue('payment/paynl/apitoken'));
        $serviceId = trim($this->getConfigValue('payment/paynl/serviceid'));
        $tokencode = trim($this->getConfigValue('payment/paynl/tokencode'));

        if (!empty($tokencode)) {
            \Paynl\Config::setTokenCode($tokencode);
        }

        if (!empty($apiToken) && !empty($serviceId)) {
            \Paynl\Config::setApiToken($apiToken);
            \Paynl\Config::setServiceId($serviceId);

            return true;
        }

        return false;
    }

    protected function getConfigValue($path)
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeValue = null;

        $store = $this->_request->getParam('store');
        $website = $this->_request->getParam('website');
        if ($store) {
            $scopeValue = $store;
            $scopeType = ScopeInterface::SCOPE_STORE;
        } elseif ($website) {
            $scopeValue = $website;
            $scopeType = ScopeInterface::SCOPE_WEBSITE;
        }

        return $this->_scopeConfig->getValue($path, $scopeType, $scopeValue);
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
