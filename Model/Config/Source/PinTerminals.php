<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use Paynl\Payment\Helper\PayHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Option\ArrayInterface;

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
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    protected $payHelper;

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
     * @param PayHelper $payHelper
     * @param CacheInterface $cache
     */
    public function __construct(
        Config $config,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PayHelper $payHelper,
        CacheInterface $cache
    ) {
        $this->_config = $config;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->payHelper = $payHelper;
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
        $terminalsArr = [];

        if ($this->_isConfigured()) {
            if ($this->_config->isPaymentMethodActive('paynl_payment_instore')) {
                [$scopeType, $scopeId] = $this->getScopeInfo();

                $value = $this->_scopeConfig->getValue('payment/paynl/terminals', $scopeType, $scopeId);

                $terminals = $value ? json_decode($value, true) : [];
                
                if (is_array($terminals)) {
                    foreach ($terminals as $terminal) {
                        array_push(
                            $terminalsArr, [
                                'name' => $terminal['name'],
                                'visibleName' => $terminal['name'],
                                'id' => $terminal['code'],
                            ]
                        );
                    }
                }

            }
        }

        $optionArr = [];
        $optionArr[0] = __('Select card terminal');
        foreach ($terminalsArr as $terminal) {
            $arr = (array)$terminal;
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
        $configured = $this->_config->getPayConfig();

        return $configured !== false;
    }

    /**
     * Summary of getScopeInfo
     * @return array<mixed|string>
     */
    private function getScopeInfo(): array
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeValue = null;

        $store = $this->_request->getParam('store');
        $website = $this->_request->getParam('website');
        if ($store) {
            $scopeType = ScopeInterface::SCOPE_STORE;
            $scopeValue = $store;
        } elseif ($website) {
            $scopeType = ScopeInterface::SCOPE_WEBSITE;
            $scopeValue = $website;
        }

        return [$scopeType, $scopeValue];
    }


    /**
     * @param string $path
     * @return string
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
