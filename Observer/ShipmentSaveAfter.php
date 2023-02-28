<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Paynl\Result\Transaction\Transaction;
use Paynl\Payment\Model\Config;
use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPayment;

class ShipmentSaveAfter implements ObserverInterface
{
    /**
     *
     * @var Magento\Store\Model\Store;
     */
    private $store;

    /**
     *
     * @var Config
     */
    private $config;

    /**
     * @var PayPayment
     */
    private $payPayment;

    /**
     * @param Config $config
     * @param Store $store
     * @param PayPayment $payPayment
     */
    public function __construct(
      Config $config,
      Store $store,
      PayPayment $payPayment
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->payPayment = $payPayment;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();
        $this->config->setStore($order->getStore());

        if ($this->config->autoCaptureEnabled()) {
            $invoiceCheck = $this->config->sherpaEnabled() ? true : !$order->hasInvoices();

            if ($order->getState() == Order::STATE_PROCESSING && $invoiceCheck) {
                $data = $order->getPayment()->getData();
                $payOrderId = $data['last_trans_id'] ?? null;

                if (!empty($payOrderId)) {
                    $bHasAmountAuthorized = !empty($data['base_amount_authorized']);
                    $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                    $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;
                    $amountPaidCheck =  $this->config->sherpaEnabled() ? true : $amountPaid === null;

                    if ($bHasAmountAuthorized && $amountPaidCheck === true && $amountRefunded === null) {
                        payHelper::logDebug('AUTO-CAPTURING(shipment-save-after) ' . $payOrderId, [], $order->getStore());
                        $bCaptureResult = false;
                        try {
                            # Handles Wuunder
                            # Handles Picqer
                            # Handles Sherpa
                            # Handles Manual made shipment
                            $this->config->configureSDK();
                            $bCaptureResult = \Paynl\Transaction::capture($payOrderId);

                            if (!$bCaptureResult) {
                                throw new \Exception('Capture failed');
                            }
                        } catch (\Exception $e) {
                            $strMessage = $e->getMessage();
                            payHelper::logDebug('Order PAY error(rest): ' . $strMessage . ' EntityId: ' . $order->getEntityId(), [], $order->getStore());

                            $strFriendlyMessage = 'Failed. Errorcode: PAY-MAGENTO2-004. See docs.pay.nl for more information';

                            if (stripos($strMessage, 'Transaction not found') !== false) {
                                $strFriendlyMessage = 'Transaction seems to be already captured/paid';
                            }
                        }

                        $order->addStatusHistoryComment(
                          __('PAY. - Performed auto-capture. Result: ') . ($bCaptureResult ? 'Success' : 'Failed') . (empty($strFriendlyMessage) ? '' : '. ' . $strFriendlyMessage)
                        )->save();

                        # Whether capture failed or succeeded, we still might have to process paid order
                        $transaction = \Paynl\Transaction::get($payOrderId);
                        if ($transaction->isPaid()) {
                            $this->payPayment->processPaidOrder($transaction, $order);
                        }
                    } else {
                        payHelper::logDebug(
                            'Auto-Capture conditions not met (yet). Amountpaid:' . $amountPaid . ' bHasAmountAuthorized: ' . ($bHasAmountAuthorized ? '1' : '0'),
                             [],
                            $order->getStore()
                        );
                    }
                } else {
                    payHelper::logDebug('Auto-Capture conditions not met (yet). No PAY-Order-id.', [], $order->getStore());
                }
            } else {
                payHelper::logDebug('Auto-capture conditions not met (yet). State: ' . $order->getState(), [], $order->getStore());
            }
        }
    }
}
