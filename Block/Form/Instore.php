<?php

namespace Paynl\Payment\Block\Form;

use Paynl\Payment\Helper\PayHelper;

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
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    protected $payHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param PayHelper $payHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        PayHelper $payHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->payHelper = $payHelper;
    }

    /**
     * Get Terminals in "key-value" format
     *
     * @return array
     */
    public function getTerminals()
    {
        $terminalArr = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        $store = $storeManager->getStore();
        $storeId = $store->getId();

        $config = $objectManager->get(\Paynl\Payment\Model\Config::class);
        $config->setStore($store);
        $configured = $config->configureSDK();

        if ($config->isPaymentMethodActive('paynl_payment_instore')) {
            if ($configured) {
                $cache = $objectManager->get(\Magento\Framework\App\CacheInterface::class);
                $cacheName = 'paynl_terminals_' . $store->getConfig('payment/paynl_payment_instore/payment_option_id') . '_' . $storeId;
                $terminalJson = $cache->load($cacheName);

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
                        $cache->save(json_encode($terminalArr), $cacheName);
                    } catch (\Paynl\Error\Error $e) {
                        $this->payHelper->logNotice('PAY.: Pinterminal error, ' . $e->getMessage());
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
     * @return string
     */
    public function getDefaultTerminal()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get(\Magento\Store\Model\Store::class);
        return $store->getConfig('payment/paynl_payment_instore/default_terminal');
    }

    /**
     * @return interger|string
     */
    public function hidePaymentOptions()
    {
        if (!empty($this->getDefaultTerminal())) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $store = $objectManager->get(\Magento\Store\Model\Store::class);
            return $store->getConfig('payment/paynl_payment_instore/hide_terminal_selection');
        }
        return 0;
    }
}
