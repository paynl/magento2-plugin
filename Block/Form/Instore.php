<?php

namespace Paynl\Payment\Block\Form;

use Magento\Framework\App\CacheInterface;
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
     * Instore constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param CacheInterface $cache
     * @param Store $store
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        CacheInterface $cache,
        Store $store
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cache = $cache;
        $this->store = $store;
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

        $this->config->setStore($store);
        $configured = $this->config->configureSDK();

        if ($this->config->isPaymentMethodActive('paynl_payment_instore')) {
            if ($configured) {
                $cacheName = 'paynl_terminals_' . $store->getConfig('payment/paynl_payment_instore/payment_option_id') . '_' . $storeId;
                $terminalJson = $this->cache->load($cacheName);

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
                        $this->cache->save(json_encode($terminalArr), $cacheName);
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
