<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Paynl\Result\Transaction\Transaction;
use Paynl\Payment\Model\Config;
use Magento\Sales\Model\Order;
use \Paynl\Payment\Helper\PayHelper;

class OrderSaveCommitAfter implements ObserverInterface
{

    /**
     *
     * @var Magento\Store\Model\Store;
     */
    private $store;

    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    public function __construct(
        Config $config,
        Store $store
    ) {
        $this->config = $config;
        $this->store = $store;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->config->setStore($order->getStore());

        if ($this->config->autoCaptureEnabled()) {
            if ($order->getState() == Order::STATE_PROCESSING && !$order->hasInvoices() && $order->hasShipments()) {
                $data = $order->getPayment()->getData();

                if (!empty($data['last_trans_id'])) {
                    $bHasAmountAuthorized = !empty($data['base_amount_authorized']);
                    $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                    $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;

                    if ($bHasAmountAuthorized && $amountPaid === null && $amountRefunded === null) {
                        payHelper::log('AUTO-CAPTURING ' . $data['last_trans_id'], payHelper::LOG_TYPE_DEBUG, [], $order->getStore());
                        try {
                            \Paynl\Config::setApiToken($this->config->getApiToken());
                            $result = \Paynl\Transaction::capture($data['last_trans_id']);
                            $strResult = 'Success';
                        } catch (\Exception $e) {
                            payHelper::log('Order PAY error: ' . $e->getMessage() . ' EntityId: ' . $order->getEntityId(), payHelper::LOG_TYPE_DEBUG, [], $order->getStore());
                            $strResult = 'Failed. Errorcode: PAY-MAGENTO2-003. See docs.pay.nl for more information';
                        }

                        $order->addStatusHistoryComment(__('PAY. - Performed auto-capture. Result: ') . $strResult, false)->save();
                    }
                }
            }
        }
    }
}
