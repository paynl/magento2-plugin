<?php

namespace Paynl\Payment\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\RequestInterface;
use \Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use \Paynl\Paymentmethods;
use Psr\Log\LoggerInterface;

class PinTerminals implements ArrayInterface
{

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    public function __construct(
        Config $config,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager, 
        LoggerInterface $logger
    ) {
        $this->_config = $config;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptions = $this->toArray();

        $arrResult = [];
        foreach ($arrOptions as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $terminalArr = [];
        if ($this->_isConfigured()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $cache = $objectManager->get(\Magento\Framework\App\CacheInterface::class);
            $storeId = $this->_request->getParam('store');
            $cacheName = 'paynl_terminals_' . $this->getConfigValue('payment/paynl_payment_instore/payment_option_id') . '_' . $storeId;
            $terminalJson = $cache->load($cacheName);
            if ($terminalJson) {
                $terminalArr = json_decode($terminalJson);
            } else {
                try {
                    $terminals = \Paynl\Instore::getAllTerminals();
                    $terminals = $terminals->getList();

                    foreach ($terminals as $terminal) {
                        $terminal['visibleName'] = $terminal['name'];
                        array_push($terminalArr, $terminal);
                    }
                    $cache->save(json_encode($terminalArr), $cacheName);
                } catch (\Paynl\Error\Error $e) {
                    $this->logger->critical('PAY.: Pinterminal error, ' . $e->getMessage());
                }
            }
        }
        $optionArr = [];
        $optionArr[0] = __('Choose the pin terminal');
        foreach ($terminalArr as $terminal) {
            $arr = (array)$terminal;
            $optionArr[$arr['id']] = $arr['visibleName'];
        }

        return $optionArr;
    }

    protected function _isConfigured()
    {
        $storeId = $this->_request->getParam('store');
        if ($storeId) {
            $store = $this->_storeManager->getStore($storeId);
            $this->_config->setStore($store);
        }
        $configured = $this->_config->configureSDK();
        if ($configured) {
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
}
