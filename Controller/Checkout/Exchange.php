<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Interceptor;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Controller\PayAction;
use Paynl\Result\Transaction\Transaction;
use \Paynl\Payment\Helper\PayHelper;

/**
 * Communicates with PAY. in order to update payment statuses in magento
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Exchange extends PayAction implements CsrfAwareActionInterface
{
    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     *
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderSender;

    /**
     *
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Magento\Framework\Controller\Result\Raw
     */
    private $result;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     *
     * @var Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $builderInterface;

    private $paynlConfig;

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * Exchange constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\Controller\Result\Raw $result
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        \Paynl\Payment\Model\Config $paynlConfig,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->paynlConfig = $paynlConfig;
        $this->builderInterface = $builderInterface;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $action = !empty($params['action']) ? strtolower($params['action']) : '';
        $payOrderId = isset($params['order_id']) ? $params['order_id'] : null;
        $orderEntityId = isset($params['extra3']) ? $params['extra3'] : null;

        if ($action == 'pending') {
            return $this->result->setContents('TRUE| Ignore pending');
        }

        if (empty($payOrderId) || empty($orderEntityId)) {            
            payHelper::logCritical('Exchange: order_id or orderEntity is not set', $params);
            return $this->result->setContents('FALSE| order_id is not set in the request');
        }

        try {
            $order = $this->orderRepository->get($orderEntityId);
            if (empty($order)) {              
                payHelper::logCritical('Cannot load order: ' . $orderEntityId);                
                throw new \Exception('Cannot load order: ' . $orderEntityId);
            }
        } catch (\Exception $e) {
            payHelper::logCritical($e, $params);
            return $this->result->setContents('FALSE| Error loading order. ' . $e->getMessage());
        }

        $this->config->setStore($order->getStore());
        \Paynl\Config::setApiToken($this->config->getApiToken());

        try {
            $transaction = \Paynl\Transaction::get($payOrderId);
        } catch (\Exception $e) {   
            payHelper::logCritical($e, $params, $order->getStore());

            return $this->result->setContents('FALSE| Error fetching transaction. ' . $e->getMessage());
        }

        if ($transaction->isPending()) {
            if ($action == 'new_ppt') {
                return $this->result->setContents("FALSE| Payment is pending");
            }
            return $this->result->setContents("TRUE| Ignoring pending");
        }

        if (method_exists($transaction, 'isPartialPayment')) {
            if ($transaction->isPartialPayment()) {
                if ($this->config->registerPartialPayments()) {
                    return $this->processPartiallyPaidOrder($order, $payOrderId);
                }
                return $this->result->setContents("TRUE| Partial payment");
            }
        }

        $payment = $order->getPayment();
        $orderEntityIdTransaction = $transaction->getExtra3();

        if ($orderEntityId != $orderEntityIdTransaction) {            
            payHelper::logCritical('Transaction mismatch ' . $orderEntityId . ' / ' . $orderEntityIdTransaction, $params, $order->getStore());
            return $this->result->setContents('FALSE|Transaction mismatch');
        }

        if ($order->getTotalDue() <= 0) {
            payHelper::logDebug($action . '. Ignoring - already paid: ' . $orderEntityId);
            if (!$this->config->registerPartialPayments()) {
                return $this->result->setContents('TRUE| Ignoring: order has already been paid');
            }
        }

        if ($action == 'capture') {
            if (!empty($payment) && $payment->getAdditionalInformation('manual_capture')) {
                payHelper::logDebug('Already captured.');
                return $this->result->setContents('TRUE| Already captured.');
            }
        }

        if ($transaction->isPaid() || $transaction->isAuthorized()) {
            return $this->processPaidOrder($transaction, $order);
        } elseif ($transaction->isCanceled()) {
            if ($order->getState() == Order::STATE_PROCESSING) {
                return $this->result->setContents("TRUE| Ignoring cancel, order is `processing`");
            } elseif ($order->isCanceled()) {
                return $this->result->setContents("TRUE| Already canceled");
            } else {
                return $this->cancelOrder($order);
            }
        }
    }

    private function cancelOrder(Order $order)
    {
        if ($this->config->isNeverCancel()) {
            return $this->result->setContents("TRUE| Not Canceled because never cancel is enabled");
        }
        if ($order->getState() == 'holded') {
            $order->unhold();
        }

        $order->cancel();
        $order->addStatusHistoryComment(__('PAY. canceled the order'));
        $this->orderRepository->save($order);

        return $this->result->setContents("TRUE| CANCELED");
    }

    private function uncancelOrder(Order $order)
    {
        if ($order->isCanceled()) {
            $state = Order::STATE_PENDING_PAYMENT;
            $productStockQty = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $productStockQty[$item->getProductId()] = $item->getQtyCanceled();
                foreach ($item->getChildrenItems() as $child) {
                    $productStockQty[$child->getProductId()] = $item->getQtyCanceled();
                    $child->setQtyCanceled(0);
                    $child->setTaxCanceled(0);
                    $child->setDiscountTaxCompensationCanceled(0);
                }
                $item->setQtyCanceled(0);
                $item->setTaxCanceled(0);
                $item->setDiscountTaxCompensationCanceled(0);
                $this->_eventManager->dispatch('sales_order_item_uncancel', ['item' => $item]);
            }
            $this->_eventManager->dispatch(
                'sales_order_uncancel_inventory',
                [
                    'order' => $order,
                    'product_qty' => $productStockQty
                ]
            );
            $order->setSubtotalCanceled(0);
            $order->setBaseSubtotalCanceled(0);
            $order->setTaxCanceled(0);
            $order->setBaseTaxCanceled(0);
            $order->setShippingCanceled(0);
            $order->setBaseShippingCanceled(0);
            $order->setDiscountCanceled(0);
            $order->setBaseDiscountCanceled(0);
            $order->setTotalCanceled(0);
            $order->setBaseTotalCanceled(0);
            $order->setState($state);
            $order->setStatus($state);

            $order->addStatusHistoryComment(__('PAY. Uncanceled order'), false);

            $this->_eventManager->dispatch('order_uncancel_after', ['order' => $order]);
        } else {
            throw new LocalizedException(__('We cannot un-cancel this order.'));
        }

        return $order;
    }

    /**
     * @param Transaction $transaction
     * @param Order $order
     * @return \Magento\Framework\Controller\Result\Raw
     */
    private function processPaidOrder(Transaction $transaction, Order $order)
    {
        if ($transaction->isPaid()) {
            $message = "PAID";
        } else {
            $message = "AUTHORIZED";
        }

        if ($order->isCanceled()) {
            try {
                $this->uncancelOrder($order);
            } catch (LocalizedException $e) {
                return $this->result->setContents('FALSE| Cannot un-cancel order: ' . $e->getMessage());
            }
            $message .= " order was uncanceled";
        }

        /** @var Interceptor $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($transaction->getId());
        $payment->setPreparedMessage('PAY. - ');
        $payment->setIsTransactionClosed(0);

        $paidAmount = $transaction->getPaidCurrencyAmount();

        if (!$this->paynlConfig->isAlwaysBaseCurrency()) {
            if ($order->getBaseCurrencyCode() != $order->getOrderCurrencyCode()) {
                # We can only register the payment in the base currency
                $paidAmount = $order->getBaseGrandTotal();
            }
        }

        # Multipayments finish
        if ($this->config->registerPartialPayments()) {
            $payments = $order->getAllPayments();
            if (count($payments) > 1) {
                if ($transaction->isPaid() && $order->getTotalDue() == 0) {
                    $paidAmount = $order->getBaseGrandTotal();
                }
            }
        }

        # Force order state to processing
        $order->setState(Order::STATE_PROCESSING);
        $paymentMethod = $order->getPayment()->getMethod();

        # Notify customer
        if ($order && !$order->getEmailSent()) {
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(__('New order email sent'))->setIsCustomerNotified(true)->save();
        }

        $newStatus = ($transaction->isAuthorized()) ? $this->config->getAuthorizedStatus($paymentMethod) : $this->config->getPaidStatus($paymentMethod);

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
                $order->setStatus(!empty($newStatus) ? $newStatus : Order::STATE_PROCESSING);
                $order->addStatusHistoryComment(__('B2B Setting: Skipped creating invoice'));
                $this->orderRepository->save($order);
                return $this->result->setContents("TRUE| " . $message . " (B2B: No invoice created)");
            }
        }

        if ($transaction->isAuthorized()) {
            $authAmount = $this->config->useMagOrderAmountForAuth() ? $order->getBaseGrandTotal() : $transaction->getCurrencyAmount();
            $payment->registerAuthorizationNotification($authAmount);
        } else {
            $payment->registerCaptureNotification($paidAmount, $this->config->isSkipFraudDetection());
        }

        $order->setStatus(!empty($newStatus) ? $newStatus : Order::STATE_PROCESSING);

        $this->orderRepository->save($order);

        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$invoice->getEmailSent()) {
            $this->invoiceSender->send($invoice);
            $order->addStatusHistoryComment(__('You notified customer about invoice #%1.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)
                ->save();
        }

        return $this->result->setContents("TRUE| " . $message);
    }

    /**
     * @param Order $order
     * @param $payOrderId
     * @return \Magento\Framework\Controller\Result\Raw
     */
    private function processPartiallyPaidOrder(Order $order, $payOrderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderPaymentFactory = $objectManager->get(\Magento\Sales\Model\Order\PaymentFactory::class);
        $returnMessage = "TRUE| Partial payment processed";

        try {
            $details = \Paynl\Transaction::details($payOrderId);

            $paymentDetails = $details->getPaymentDetails();
            $transactionDetails = $paymentDetails['transactionDetails'];
            $firstPayment = count($transactionDetails) == 1;
            $totalpaid =0;
            foreach ($transactionDetails as $_dt) {
                $totalpaid += $_dt['amount']['value'];
            }
            $_detail = end($transactionDetails);

            $subProfile = $_detail['orderId'];
            $profileId = $_detail['paymentProfileId'];
            $method = $_detail['paymentProfileName'];
            $amount = $_detail['amount']['value'] / 100;
            $currency = $_detail['amount']['currency'];
            $methodCode = $this->config->getPaymentmethodCode($profileId);

            /** @var Interceptor $orderPayment */
            if (!$firstPayment) {
                $orderPayment = $orderPaymentFactory->create();
            } else {
                $orderPayment = $order->getPayment();
            }
            $orderPayment->setMethod($methodCode);
            $orderPayment->setOrder($order);
            $orderPayment->setBaseAmountPaid($amount);
            $orderPayment->save();

            $transactionBuilder = $this->builderInterface->setPayment($orderPayment)
                ->setOrder($order)
                ->setTransactionId($subProfile)
                ->setFailSafe(true)
                ->build('capture')
                ->setAdditionalInformation(
                    \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                    ["Paymentmethod" => $method, "Amount" => $amount, "Currency" => $currency]
                );
            $transactionBuilder->save();

            $order->addStatusHistoryComment(__('PAY.: Partial payment received: '.$subProfile.' - Amount ' . $currency . ' ' . $amount . ' Method: ' . $method));
            $order->setTotalPaid($totalpaid / 100);

            $this->orderRepository->save($order);

        } catch (\Exception $e) {
            $returnMessage = 'TRUE| Failed processing partial payment'. $e->getMessage();
        }

        return $this->result->setContents($returnMessage);
    }
}
