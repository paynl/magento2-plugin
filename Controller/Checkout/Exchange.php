<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

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



/**
 * Description of Index
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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

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
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Controller\Result\Raw $result
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        \Paynl\Payment\Model\Config $paynlConfig,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface
    )
    {
        $this->result = $result;
        $this->config = $config;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->paynlConfig = $paynlConfig;
        $this->builderInterface = $builderInterface;

        parent::__construct($context);
    }

    public function execute()
    {
        \Paynl\Config::setApiToken($this->config->getApiToken());

        $params = $this->getRequest()->getParams();
        $action = !empty($params['action']) ? strtolower($params['action']) : '';

        if ($action == 'pending') {
            return $this->result->setContents('TRUE| Ignore pending');
        }

        if (!isset($params['order_id'])) {
            $this->logger->critical('Exchange: order_id is not set in the request', $params);

            return $this->result->setContents('FALSE| order_id is not set in the request');
        }

        try {
            $transaction = \Paynl\Transaction::get($params['order_id']);
        } catch (\Exception $e) {
            $this->logger->critical($e, $params);

            return $this->result->setContents('FALSE| Error fetching transaction. ' . $e->getMessage());
        }

        if(method_exists($transaction, 'isPartialPayment')) {
            if($transaction->isPartialPayment()) {
                return $this->result->setContents("TRUE| Partial payment");
            }
        }

        if ($transaction->isPending()) {
            if ($action == 'new_ppt') {
                return $this->result->setContents("FALSE| Payment is pending");
            }
            return $this->result->setContents("TRUE| Ignoring pending");
        }

        $orderEntityId = $transaction->getExtra3();
        /** @var Order $order */
        $order = $this->orderRepository->get($orderEntityId);

        if (empty($order)) {
            $this->logger->critical('Cannot load order: ' . $orderEntityId);

            return $this->result->setContents('FALSE| Cannot load order');
        }
        if ($order->getTotalDue() <= 0) {
            $this->logger->debug('Total due <= 0, so not touching the status of the order: ' . $orderEntityId);

            return $this->result->setContents('TRUE| Ignoring: order has already been paid');
        }
        if ($action == 'capture') {
            $payment = $order->getPayment();
            if (!empty($payment) && $payment->getAdditionalInformation('manual_capture')) {
                $this->logger->debug('Already captured.');
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
        $payment->setTransactionId(
            $transaction->getId()
        );

        $payment->setPreparedMessage('PAY. - ');
        $payment->setIsTransactionClosed(0);

        $paidAmount = $transaction->getPaidCurrencyAmount();

        if (!$this->paynlConfig->isAlwaysBaseCurrency()) {
            if ($order->getBaseCurrencyCode() != $order->getOrderCurrencyCode()) {
                # We can only register the payment in the base currency
                $paidAmount = $order->getBaseGrandTotal();
            }
        }

        # Force order state to processing
        $order->setState(Order::STATE_PROCESSING);
        $paymentMethod = $order->getPayment()->getMethod();

        if ($transaction->isAuthorized()) {
            $statusAuthorized = $this->config->getAuthorizedStatus($paymentMethod);
            $order->setStatus(!empty($statusAuthorized) ? $statusAuthorized : Order::STATE_PROCESSING);
        } else {
            $statusPaid = $this->config->getPaidStatus($paymentMethod);
            $order->setStatus(!empty($statusPaid) ? $statusPaid : Order::STATE_PROCESSING);
        }

        # Notify customer
        if ($order && !$order->getEmailSent()) {
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(__('New order email sent'))->setIsCustomerNotified(true)->save();
        }

        # Skip creation of invoice for B2B if enabled
        if ($this->config->ignoreB2BInvoice($paymentMethod)) {
            $orderCompany = $order->getBillingAddress()->getCompany();
            if(!empty($orderCompany)) {
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
}
