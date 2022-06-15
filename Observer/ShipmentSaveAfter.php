<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Paynl\Result\Transaction\Transaction;
use Paynl\Payment\Model\Config;
use Magento\Sales\Model\Order;
use \Paynl\Payment\Helper\PayHelper;

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

    public function __construct(
        Config $config,
        Store $store
    ) {
        $this->config = $config;
        $this->store = $store;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();
        $this->config->setStore($order->getStore());

        if ($this->config->autoCaptureEnabled()) {
            if ($order->getState() == Order::STATE_PROCESSING && !$order->hasInvoices()) {
                $data = $order->getPayment()->getData();

                if (!empty($data['last_trans_id'])) {
                    $bHasAmountAuthorized = !empty($data['base_amount_authorized']);
                    $amountPaid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
                    $amountRefunded = isset($data['amount_refunded']) ? $data['amount_refunded'] : null;

                    if ($bHasAmountAuthorized && $amountPaid === null && $amountRefunded === null) {
                        payHelper::logNotice('AUTO-CAPTURING (rest)' . $data['last_trans_id'], [], $order->getStore());
                        try {
                            \Paynl\Config::setApiToken($this->config->getApiToken());

                            $result = json_decode(file_get_contents('php://input'), true);

                            # Auto Capture for Wuunder
                            if ($result['action'] === "track_and_trace_updated") {

                                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();                            
                                $transaction = \Paynl\Transaction::get($data['last_trans_id']);

                                /** @var Interceptor $payment */
                                $payment = $order->getPayment();
                                $payment->setAdditionalInformation('manual_capture', 'true');
                                $payment->getOrder()->save();
                                $payment->setTransactionId($transaction->getId());
                                $payment->setPreparedMessage('PAY. - ');
                                $payment->setIsTransactionClosed(0);

                                $transactionPaid = [
                                    $transaction->getCurrencyAmount(),
                                    $transaction->getPaidCurrencyAmount(),
                                    $transaction->getPaidAmount(),
                                ];

                                if (!in_array($order->getGrandTotal(), $transactionPaid)) {
                                    $this->logger->debug('Validation error: Paid amount does not match order amount. paidAmount: ' . implode(' / ', $transactionPaid) . ', orderAmount:' . $order->getGrandTotal());
                                }

                                $paidAmount = $order->getGrandTotal();

                                if (!$this->config->isAlwaysBaseCurrency()) {
                                    if ($order->getBaseCurrencyCode() != $order->getOrderCurrencyCode()) {
                                        # We can only register the payment in the base currency
                                        $paidAmount = $order->getBaseGrandTotal();
                                    }
                                }

                                $paymentMethod = $order->getPayment()->getMethod();
                                $orderRepository = $objectManager->create('Magento\Sales\Model\OrderRepository');

                                # Skip creation of invoice for B2B if enabled
                                if ($this->config->ignoreB2BInvoice($paymentMethod)) {
                                    $orderCompany = $order->getBillingAddress()->getCompany();
                                    if (!empty($orderCompany)) {
                                        # Create transaction
                                        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
                                        $transactionMessage = __('PAY. - Captured amount of %1.', $formatedPrice);
                                        $transactionBuilder = $this->builderInterface->setPayment($payment)->setOrder($order)->setTransactionId($transaction->getId())->setFailSafe(true)->build('capture');
                                        $payment->addTransactionCommentsToOrder($transactionBuilder, $transactionMessage);
                                        $payment->setParentTransactionId(null);
                                        $payment->save();
                                        $transactionBuilder->save();

                                        # Change amount paid manually
                                        $order->setTotalPaid($order->getGrandTotal());
                                        $order->setBaseTotalPaid($order->getBaseGrandTotal());
                                        $order->addStatusHistoryComment(__('B2B Setting: Skipped creating invoice'));
                                        $orderRepository->save($order);
                                    }
                                } else {
                                    $payment->registerCaptureNotification($paidAmount, $this->config->isSkipFraudDetection());

                                    $orderRepository->save($order);

                                    $invoice = $payment->getCreatedInvoice();
                                    if ($invoice && !$invoice->getEmailSent()) {
                                        $invoiceSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
                                        $invoiceSender->send($invoice);
                                        $order->addStatusHistoryComment(__('You notified customer about invoice #%1.', $invoice->getIncrementId()))
                                            ->setIsCustomerNotified(true)
                                            ->save();

                                        $order->setTotalPaid($order->getGrandTotal());
                                        $order->setBaseTotalPaid($order->getBaseGrandTotal());
                                        $order->setTotalDue(0);
                                        $orderRepository->save($order);


                                        $order->addStatusHistoryComment(__('PAY. - Invoices: ') . $order->getInvoiceCollection()->count(), false)->save();
                                    }
                                }
                            }

                            \Paynl\Transaction::capture($data['last_trans_id']);
                            $strResult = 'Success';
                        } catch (\Exception $e) {
                            payHelper::logCritical('Order PAY error (rest): ' . $e->getMessage() . ' EntityId: ' . $order->getEntityId(), [], $order->getStore());
                            $strResult = 'Failed. Errorcode: PAY-MAGENTO2-004. See docs.pay.nl for more information';
                        }

                        $order->addStatusHistoryComment(__('PAY. - Performed auto-capture (rest). Result: ') . $strResult, false)->save();
                    }
                }
            }
        }
    }
}
