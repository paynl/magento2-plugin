<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source\Available;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\Method\Factory as PaymentMethodFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use \Magento\Framework\Option\ArrayInterface;
use \Paynl\Paymentmethods;
use \Paynl\Payment\Model\Config;
use \Paynl\Payment\Model\ConfigProvider;
use Magento\Payment\Helper\Data as PaymentHelper;

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
     * @var ConfigProvider
     */
    protected $_configProvider;

    /**
     * @var  Store
     * */
    private $store;

    /**
     * @var  ConfigWriter
     * */
    private $configWriter;

    /**
     * @var  CacheTypeList
     * */
    private $cacheTypeList;

    /**
     * @var  Changed
     * */
    private $changed;
    /**
     * @var ConfigProvider
     */
    protected $_paymentHelper;


    public function __construct(
        PaymentHelper $paymentHelper,
        Config $config,
        ConfigProvider $configProvider,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        PaymentMethodFactory $paymentMethodFactory,
        Store $store,
        WriterInterface $configWriter,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    )
    {
        $this->_paymentHelper = $paymentHelper;
        $this->_config = $config;
        $this->_configProvider = $configProvider;
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_paymentmethodFactory = $paymentMethodFactory;
        $this->store = $store;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->setDefaults();
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
        $configured = $this->configureSDK();
        if (!$configured) {
            return [0 => __('Enter your API-token and ServiceId first')];
        }
        try {
            if ($this->_isAvailable()) {
                return [0 => __('No'), 1 => __('Yes')];
            } else {
                return [0 => __('Not available, you can enable this on admin.pay.nl')];
            }
        } catch (\Exception $e) {
            return [0 => 'Error: ' . $e->getMessage()];
        }

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

    protected function getPaymentOptionId()
    {
        $method = $this->_paymentmethodFactory->create($this->_class);
        if ($method instanceof PaymentMethod) {
            return $method->getPaymentOptionId();
        }
        return null;
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

    protected function _isAvailable()
    {
        $configured = $this->configureSDK();
        if ($configured) {
            $paymentOptionId = $this->getPaymentOptionId();

            $list = Paymentmethods::getList();

            if (isset($list[$paymentOptionId])) {
                return true;
            }
        }

        return false;
    }

    public function setDefaults()
    {
        $configured = $this->configureSDK();
        if ($configured) {
            $Paymentmethods = Paymentmethods::getList();
            $MethodCodes = $this->_configProvider->getMethodCodes();

            $this->changed = false;

            foreach ($MethodCodes as $key => $MethodCode) {

                $MagentoMethod = $this->_paymentHelper->getMethodInstance($MethodCode);
                $PaymentMethodId = $MagentoMethod->getPaymentOptionId();

                if (isset($Paymentmethods[$PaymentMethodId])) {
                    $method = $Paymentmethods[$PaymentMethodId];

                    if (isset($method['min_amount'])) {
                        $this->setDefaultValue('payment/' . $MethodCode . '/min_order_total', $method['min_amount']);
                    }
                    if (isset($method['max_amount'])) {
                        $this->setDefaultValue('payment/' . $MethodCode . '/max_order_total', $method['max_amount']);
                    }
                    if (isset($method['brand']['public_description'])) {
                        $this->setDefaultValue('payment/' . $MethodCode . '/instructions', $method['brand']['public_description']);
                    }
                }
            }

            //Clean the cache or esle it won't show the changes
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

            //Refresh the page to apply the defaults after opening Payment methodes
            if ($this->changed) {
                header("Refresh:0");
            }
        }
    }

    private function setDefaultValue($configName, $value)
    {
        if (strlen($this->store->getConfig($configName)) == 0) {
            $this->configWriter->save($configName, $value);
            if (strlen($value) > 0) {
                $this->changed = true;
            }
        }
    }
}
