<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Paynl\Result\Transaction\Transaction;
use Paynl\Payment\Model\Config;
use Magento\Sales\Model\Order;

class ShipmentSaveAfter implements ObserverInterface
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

        $this->logger->debug("SHIPMENT OBSERVER CALLED");

        if ($this->config->autoCaptureEnabled()) {
            $shipment = $observer->getEvent()->getShipment();
            $order = $shipment->getOrder();
            if ($order->getState() == Order::STATE_PROCESSING && !$order->hasInvoices() && $order->hasShipments()) {
                $data = $order->getPayment()->getData();

                if (!empty($data['last_trans_id'])) {
                    $bHasAmountAuthorized = !empty($data['base_amount_authorized']);
                    $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                    $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;

                    if ($bHasAmountAuthorized && is_null($amountPaid) && is_null($amountRefunded)) {
                        $this->logger->debug('PAY.: AUTO-CAPTURING ' . $data['last_trans_id']);
                        try {
                            \Paynl\Config::setApiToken($this->config->getApiToken());
                            $result = \Paynl\Transaction::capture($data['last_trans_id']);
                            $strResult = 'Success';
                        } catch (\Exception $e) {
                            $this->logger->debug('Order PAY error: ' . $e->getMessage() . ' EntityId: ' . $order->getEntityId());
                            $strResult = 'Failed. Errorcode: PAY-MAGENTO2-003. See docs.pay.nl for more information';
                        }

                        $order->addStatusHistoryComment(__('PAY. - Performed auto-capture. Result: ') . $strResult, false)->save();
                    }
                }
            }
        }
    }

}
