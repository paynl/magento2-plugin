<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Option\ArrayInterface;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use Paynl\Paymentmethods;

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
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * constructor.
     * @param Config $config
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param  \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     */
    public function __construct(
        Config $config,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CacheInterface $cache
    ) {
        $this->_config = $config;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_cache = $cache;
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
            if ($this->_config->isPaymentMethodActive('paynl_payment_instore')) {
                $storeId = $this->_request->getParam('store');
                $cacheName = 'paynl_terminals_' . $this->getConfigValue('payment/paynl_payment_instore/payment_option_id') . '_' . $storeId;
                $terminalJson = $this->_cache->load($cacheName);
                if ($terminalJson) {
                    $terminalArr = json_decode($terminalJson);
                } else {
                    try {
                        $terminals = \Paynl\Instore::getAllTerminals();
                        $terminals = $terminals->getList();

                        if (!is_array($terminals)) {
                            $terminals = [];
                        }

                        foreach ($terminals as $terminal) {
                            $terminal['visibleName'] = $terminal['name'];
                            array_push($terminalArr, $terminal);
                        }
                        $this->_cache->save(json_encode($terminalArr), $cacheName);
                    } catch (\Paynl\Error\Error $e) {
                        payHelper::logNotice('PAY.: Pinterminal error, ' . $e->getMessage());
                    }
                }
            }
        }
        $optionArr = [];
        $optionArr[0] = __('Choose the pin terminal');
        foreach ($terminalArr as $terminal) {
            $arr = (array) $terminal;
            $optionArr[$arr['id']] = $arr['visibleName'];
        }

        return $optionArr;
    }

    /**
     * @return boolean
     */
    protected function _isConfigured() // phpcs:ignore
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

    /**
     * @param string $path
     * @return boolean
     */
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
