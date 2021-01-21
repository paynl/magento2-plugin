<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Paynl\Result\Transaction\Transaction;
use Paynl\Payment\Model\Config;

class OrderSaveAfter implements ObserverInterface
{

    /**
     *
     * @var Magento\Store\Model\Store;
     */
    private $store;

    /**
     *
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Store $store
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->store = $store;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $auto_capture = $this->store->getConfig('payment/paynl/auto_capture');
        
        if (!$order->hasInvoices() && $order->hasShipments() && $order->getState() == 'processing' && $auto_capture == 1) {
            $payment = $order->getPayment();
            $data = $payment->getData();          
            if (isset($data['last_trans_id'])) {
                $transactionId = $data['last_trans_id'];                
                $amountAuthorized = isset($data['base_amount_authorized']) ? (float)$data['base_amount_authorized'] : null;
                $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;
                if ($amountAuthorized > 0 && !is_null($amountAuthorized) && is_null($amountPaid) && is_null($amountRefunded) && !empty($transactionId) && !is_null($transactionId)) {
                    $order->addStatusHistoryComment('PAY. -Order has been shipped, but no invoice was found. Capturing the payment automatically.', false);
                    try {
                        \Paynl\Config::setApiToken($this->config->getApiToken());
                        $result = \Paynl\Transaction::capture($transactionId);
                    } catch (\Paynl\Error\Error $e) {
                        $this->logger->notice('Order PAY error: ' . $e->getMessage() . ' EntityId: ' . $order->getEntityId());
                        
                    }
                }
            }
        }

        $this->logger->notice('Order PAY Status:' . $order->getStatus() . ' Order PAY State:' . $order->getState() . ' EntityId: ' . $order->getEntityId());
    }
}
