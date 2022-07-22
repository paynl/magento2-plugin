<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Paynl\Result\Transaction\Transaction;
use Paynl\Payment\Model\Config;
use Magento\Sales\Model\Order;
use \Paynl\Payment\Helper\PayHelper;
use \Paynl\Payment\Model\PayPayment;

class ShipmentSaveAfter implements ObserverInterface
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

    /**
     * @var PayPayment
     */
    private $payPayment;

    public function __construct(
        Config $config,
        Store $store,
        PayPayment $payPayment
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->payPayment = $payPayment;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();
        $this->config->setStore($order->getStore());

        if ($this->config->autoCaptureEnabled())
        {
            if ($order->getState() == Order::STATE_PROCESSING && !$order->hasInvoices()) {
                $data = $order->getPayment()->getData();
                $payOrderId = $data['last_trans_id'] ?? null;

                if (!empty($transactionId)) {
                    $bHasAmountAuthorized = !empty($data['base_amount_authorized']);
                    $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                    $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;

                    if ($bHasAmountAuthorized && $amountPaid === null && $amountRefunded === null) {
                        payHelper::logNotice('AUTO-CAPTURING(shipment-save-after) ' . $transactionId, [], $order->getStore());
                        try {
                            \Paynl\Config::setApiToken($this->config->getApiToken());

                            # Auto Capture for Wuunder
                            if ($this->config->wuunderAutoCaptureEnabled() || $this->config->sherpaEnabled())
                            {
                                \Paynl\Transaction::capture($transactionId);
                                $transaction = \Paynl\Transaction::get($transactionId);
                                $this->payPayment->processPaidOrder($transaction, $order);
                            }
                            else
                            {
  //                                \Paynl\Transaction::capture($transactionId);

                                payHelper::logCritical('not doing aauto-capture in else', [], $order->getStore());
                            }
                            $strResult = 'Success';
                        } catch (\Exception $e) {
                            payHelper::logCritical('Order PAY error(rest): ' . $e->getMessage() . ' EntityId: ' . $order->getEntityId(), [], $order->getStore());
                            $strResult = 'Failed. Errorcode: PAY-MAGENTO2-004. See docs.pay.nl for more information';
                        }

                        $order->addStatusHistoryComment(__('PAY. - Performed auto-capture(rest). Result: ') . $strResult, false)->save();
                    }
                }
            }
        }
    }
}
