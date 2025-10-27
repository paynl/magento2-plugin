<?php

namespace Paynl\Payment\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Url as BackendUrl;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;

class CardRefundForm extends Template
{
    private $orderRepository;
    protected $payHelper;
    protected $backendUrl;
    public $storeManager;
    public $config;
    public $cache;
    public $store;

    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param PayHelper $payHelper
     * @param BackendUrl $backendUrl
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param CacheInterface $cache
     * @param Store $store
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        PayHelper $payHelper,
        BackendUrl $backendUrl,
        StoreManagerInterface $storeManager,
        Config $config,
        CacheInterface $cache,
        Store $store
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->payHelper = $payHelper;
        $this->backendUrl = $backendUrl;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cache = $cache;
        $this->store = $store;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        $params = $this->getRequest()->getParams();
        $returnUrl = isset($params['return_url']) ? urldecode($params['return_url']) : null;
        return $returnUrl;
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->backendUrl->getUrl('paynl/order/cardrefund');
    }

    /**
     * Get Terminals in "key-value" format
     *
     * @return array
     */
    public function getTerminals()
    {
        $terminalArr = [];

        $order = $this->getOrder();
        $store = $order->getStore();
        $storeId = $store->getId();
        $scopeId = $storeId ?? 0;

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
            $arr = (array)$terminal;
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
     * @return mixed|null
     */
    public function getOrderId()
    {
        $params = $this->getRequest()->getParams();
        return isset($params['order_id']) ? $params['order_id'] : null;
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        $orderId = $this->getOrderId();
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $e) {
            $this->payHelper->logCritical($e, $params);
        }
        return $order;
    }
}
