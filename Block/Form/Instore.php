<?php

namespace Paynl\Payment\Block\Form;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;

/**
 * Block for Instore payment method form
 */
class Instore extends \Magento\Payment\Block\Form
{
    /**
     * Instore template
     *
     * @var string
     */
    protected $_template = 'form/instore.phtml';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Paynl\Payment\Model\Config
     */
    public $config;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    public $cache;

    /**
     * @var \Magento\Store\Model\Store
     */
    public $store;

    /**
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    protected $payHelper;

    /**
     * Instore constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param CacheInterface $cache
     * @param Store $store
     * @param PayHelper $payHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        CacheInterface $cache,
        Store $store,
        PayHelper $payHelper
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cache = $cache;
        $this->store = $store;
        $this->payHelper = $payHelper;
        parent::__construct($context);
    }

    /**
     * Get Terminals in "key-value" format
     *
     * @return array
     */
    public function getTerminals()
    {
        $terminalArr = [];

        $store = $this->storeManager->getStore();
        $storeId = $store->getId();

        $scopeId = 0;
        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        }

        $this->config->setStore($store);

        if ($this->config->isPaymentMethodActive('paynl_payment_instore')) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $terminals = json_decode($this->_scopeConfig->getValue('payment/paynl/terminals', $scopeType, $scopeId), true);

            if (is_array($terminals)) {
                foreach ($terminals as $terminal) {
                    array_push($terminalsArr, [
                            'name' => $terminal['name'],
                            'visibleName' => $terminal['name'],
                            'id' => $terminal['code'],
                        ]
                    );
                }
            }
        }

        $optionArr = [];
        $optionArr[0] = __('Select card terminal');
        foreach ($terminalArr as $terminal) {
            $arr = (array) $terminal;
            $optionArr[$arr['id']] = $arr['visibleName'];
        }

        return $optionArr;
    }

    /**
     * @return string|integer
     */
    public function getDefaultTerminal()
    {
        return $this->store->getConfig('payment/paynl_payment_instore/default_terminal');
    }

    /**
     * @return string|integer
     */
    public function hidePaymentOptions()
    {
        if (!empty($this->getDefaultTerminal())) {
            return $this->store->getConfig('payment/paynl_payment_instore/hide_terminal_selection');
        }
        return 0;
    }
}
