<?php

namespace Paynl\Payment\Model\Config\Source\Available;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\Factory as PaymentMethodFactory;
use Magento\Store\Model\ScopeInterface;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use PayNL\Sdk\Config\Config as PaySDKConfig;
use PayNL\Sdk\Model\Request\ServiceGetConfigRequest;

abstract class Available implements ArrayInterface
{
    /**
     * @var string The name of the class for this method
     */
    protected $_class;

    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var PaymentMethodFactory
     */
    protected $_paymentmethodFactory;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var mixed|bool
     */
    protected static mixed $list = false;

    /**
     * Available construct
     *
     * @param Config $config
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentMethodFactory $paymentMethodFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Config                                     $config,
        RequestInterface                           $request,
        ScopeConfigInterface                       $scopeConfig,
        PaymentMethodFactory                       $paymentMethodFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_config = $config;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_paymentmethodFactory = $paymentMethodFactory;
        $this->_storeManager = $storeManager;
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
     * @return array
     */
    private function getScopes()
    {
        $storeId = $this->_request->getParam('store');
        $websiteId = $this->_request->getParam('website');

        $scope = 'default';
        $scopeId = 0;

        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        }
        if ($websiteId) {
            $scope = 'websites';
            $scopeId = $websiteId;
        }

        return ['scope' => $scope, 'scopeId' => $scopeId];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $arrScopes = $this->getScopes();
        $scope = $arrScopes['scope'];
        $scopeId = $arrScopes['scopeId'];

        $this->_config->setScope($scope, $scopeId);

        $configured = $this->_config->getPayConfig() !== false;
        if (!$configured) {
            return [0 => __('Enter your API token and SL-code first')];
        }
        try {
            if ($this->_isAvailable()) {
                return [0 => __('No'), 1 => __('Yes')];
            } else {
                return [0 => __('Not active, activate on My.pay.nl first')];
            }
        } catch (\Exception $e) {
            return [0 => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * @return PaymentMethod|null
     */
    protected function getPaymentOptionId()
    {
        $method = $this->_paymentmethodFactory->create($this->_class);
        if ($method instanceof PaymentMethod) {
            return $method->getPaymentOptionId();
        }
        return null;
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

    /**
     * @return bool
     */
    protected function _isAvailable() // phpcs:ignore
    {
        $arrScopes = $this->getScopes();
        $scope = $arrScopes['scope'];
        $scopeId = $arrScopes['scopeId'];

        if (self::$list === false) {
            $this->_config->setScope($scope, $scopeId);
            $config = $this->_config->getPayConfig();

            if ($config !== false) {
                try {
                    $request = new ServiceGetConfigRequest($this->_config->getServiceId());
                    $service = $request->setConfig($config)->start();

                    $this->_config->saveCoresToConfig($service->getCores(), $scope);
                    $this->_config->saveTerminalsToConfig($service->getTerminals(), $scope,$scopeId);

                    $list = [];
                    foreach ($service->getPaymentMethods() as $method) {
                        $list[$method->getId()] = $method->getName();
                    }

                    self::$list = $list;
                } catch (\Exception $e) {
                    self::$list = $e;
                }
            }
        }

        $paymentOptionId = $this->getPaymentOptionId();

        if (!empty($paymentOptionId)) {
            if (is_array(self::$list)) {
                return isset(self::$list[$paymentOptionId]);
            }
        }
        return false;
    }
}
