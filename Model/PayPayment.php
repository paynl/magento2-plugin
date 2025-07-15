<?php

namespace Paynl\Payment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Interceptor;
use Magento\Sales\Model\OrderRepository;
use Magento\SalesRule\Model\Coupon\UpdateCouponUsages;
use Paynl\Payment\Helper\PayHelper;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use PayNL\Sdk\Model\Pay\PayOrder;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class PayPayment
{
    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $builderInterface;

    /**
     * @var Magento\Sales\Model\Order\PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var Magento\SalesRule\Model\Coupon\UpdateCouponUsages
     */
    private $updateCouponUsages;

    /**
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory;
     */
    private $cmFac;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService;
     */
    private $cmService;

    /**
     * Constructor.
     *
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface
     * @param \Magento\Sales\Model\Order\PaymentFactory $paymentFactory
     * @param UpdateCouponUsages $updateCouponUsages
     * @param PayHelper $payHelper
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $cmFac
     * @param \Magento\Sales\Model\Service\CreditmemoService $cmService
     */
    public function __construct(
        InvoiceService $invoiceService,
        InvoiceRepositoryInterface $invoiceRepository,
        \Paynl\Payment\Model\Config $config,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface,
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory,
        UpdateCouponUsages $updateCouponUsages,
        PayHelper $payHelper,
        \Magento\Sales\Model\Order\CreditmemoFactory $cmFac,
        \Magento\Sales\Model\Service\CreditmemoService $cmService
    ) {
        $this->invoiceService = $invoiceService;
        $this->invoiceRepository = $invoiceRepository;
        $this->eventManager = $eventManager;
        $this->config = $config;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->builderInterface = $builderInterface;
        $this->paymentFactory = $paymentFactory;
        $this->updateCouponUsages = $updateCouponUsages;
        $this->payHelper = $payHelper;
        $this->cmService = $cmService;
        $this->cmFac = $cmFac;
    }

    /**
     * @param Order $order
     * @return boolean
     */
    public function cancelOrder(Order $order)
    {
        $returnResult = false;
        try {
            if ($order->getState() == 'holded') {
                $order->unhold();
            }
            $order->getPayment()->setAdditionalInformation('cancelByExchange', true);

            $order->cancel();
            $order->addStatusHistoryComment(__('Pay. - Order cancelled'));
            $this->orderRepository->save($order);
            if (!empty($order->getCouponCode())) {
                $this->updateCouponUsages->execute($order, false);
            }
            $returnResult = true;
        } catch (\Exception $e) {
            throw new \Exception('Cannot cancel order: ' . $e->getMessage());
        }

        return $returnResult;
    }

    /**
     * @param Order $order
     * @return void
     */
    public function uncancelOrder(Order $order)
    {
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
            $this->eventManager->dispatch('sales_order_item_uncancel', ['item' => $item]);
        }
        $this->eventManager->dispatch(
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
        $order->addStatusHistoryComment(__('Pay. - order uncancelled'), false);
        if (!empty($order->getCouponCode())) {
            $this->updateCouponUsages->execute($order, true);
        }
        $this->eventManager->dispatch('order_uncancel_after', ['order' => $order]);
    }

    /**
     * @param Order $order
     * @return true
     * @throws \Exception
     */
    public function chargebackOrder($order)
    {
        if (!$this->config->chargebackFromPayEnabled() || $order->getTotalDue() != 0 || $order->getBaseTotalRefunded() == $order->getBaseGrandTotal()) {
            throw new \Exception("Ignoring chargeback");
        }
        try {
            $creditmemo = $this->cmFac->createByOrder($order);
            $this->cmService->refund($creditmemo);
            $order->addStatusHistoryComment(__('Pay. - Chargeback initiated by customer'))->save();
        } catch (\Exception $e) {
            $this->payHelper->logDebug('Chargeback failed:', ['error' => $e->getMessage(), 'orderEntityId' => $orderEntityId]);
            throw new \Exception('Could not chargeback');
        }
        return true;
    }

    /**
     * @param integer $orderEntityId
     * @return true
     * @throws \Exception
     */
    public function refundOrder($orderEntityId)
    {
        try {
            $order = $this->orderRepository->get($orderEntityId);
            $creditmemo = $this->cmFac->createByOrder($order);
            $this->cmService->refund($creditmemo);

            $order->addStatusHistoryComment(__('Pay. - Refund initiated from Pay.'))->save();
        } catch (\Exception $e) {
            throw new \Exception('Could not refund ' . $e->getMessage());
        }
        return true;
    }

    /**
     * Update the order to refunded
     *
     * @param integer $orderEntityId
     * @return true
     * @throws \Exception
     */
    public function cardRefundOrder($orderEntityId)
    {
        $order = $this->orderRepository->get($orderEntityId);
        if ($order->getTotalDue() != 0 || $order->getBaseTotalRefunded() == $order->getBaseGrandTotal()) {
            throw new \Exception("Ignoring cardRefundOrder (" . $order->getTotalDue() . '|' . $order->getBaseTotalRefunded() . '|' . $order->getBaseGrandTotal());
        }
        try {
            $creditmemo = $this->cmFac->createByOrder($order);
            $this->cmService->refund($creditmemo);
            $order->addStatusHistoryComment(__('Pay. - Refund via Card initiated from Magento2 Backend'))->save();
        } catch (\Exception $e) {
            throw new \Exception('Could not process Refund via Card');
        }
        return true;
    }

    /**
     * Refactored version of processPaidOrder - moderately split into logical parts
     *
     * @param PayOrder $payOrder
     * @param Order $magOrder
     * @param $paymentProfileId
     * @return bool
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processPaidOrder(PayOrder $payOrder, Order $magOrder, $paymentProfileId = null)
    {
        $returnResult = false;
        $multiShippingOrder = false;
        $orderProcessList = $this->getOrdersToProcess($magOrder, $multiShippingOrder);

        $paymentMethod = $magOrder->getPayment()->getMethod();
        $newStatus = $payOrder->isAuthorized() ? $this->config->getAuthorizedStatus($paymentMethod) : $this->config->getPaidStatus($paymentMethod);
        $transactionPaid = [$payOrder->getAmount()];

        foreach ($orderProcessList as $order) {
            $this->initializePayment($order, $payOrder, $paymentProfileId, $paymentMethod);
            $this->validateAmount($order, $transactionPaid, $multiShippingOrder);
            $this->processCustomerNotification($order);
            if (($this->config->ignoreB2BInvoice($paymentMethod) && !empty($order->getBillingAddress()->getCompany()))
                || !$this->config->invoiceCreation()
            ) {
                $returnResult = $this->processB2BPayment($payOrder, $order, $order->getPayment(), $newStatus);
            } else {
                $this->processPayment($payOrder, $order);
                $order->setStatus(!empty($newStatus) ? $newStatus : Order::STATE_PROCESSING);
                $this->orderRepository->save($order);
                $this->sendInvoiceIfNeeded($order);
                $returnResult = true;
            }
        }

        return $returnResult;
    }

    /**
     * @param Order $magOrder
     * @param bool $multiShippingOrder
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getOrdersToProcess(Order $magOrder, bool &$multiShippingOrder): array
    {
        $orderProcessList = [];
        $order_ids = $magOrder->getPayment()->getAdditionalInformation('order_ids');

        if (!empty($order_ids)) {
            $multiShippingOrder = true;
            $orderids = json_decode($order_ids, true);
            foreach ($orderids as $orderId) {
                $this->payHelper->logDebug('Multishipping order:', ['orderid: ' => $orderId]);
                $orderProcessList[] = $this->orderRepository->get($orderId);
            }
        } else {
            if ($magOrder->isCanceled() || $magOrder->getTotalCanceled() == $magOrder->getGrandTotal()) {
                $this->uncancelOrder($magOrder);
            }
            $orderProcessList[] = $magOrder;
        }

        return $orderProcessList;
    }

    /**
     * @param Order $order
     * @param PayOrder $payOrder
     * @param $paymentProfileId
     * @param $originalPaymentMethod
     * @return void
     * @throws \Exception
     */
    private function initializePayment(Order $order, PayOrder $payOrder, $paymentProfileId, $originalPaymentMethod)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($payOrder->getOrderId());
        $payment->setPreparedMessage('Pay. - ');
        $payment->setIsTransactionClosed(0);

        if ($this->config->getFollowPaymentMethod() && !empty($paymentProfileId)) {
            $transactionMethod = $this->config->getPaymentMethod($paymentProfileId);
            if (!empty($transactionMethod['code']) && $transactionMethod['code'] !== $originalPaymentMethod) {
                $payment->setMethod($transactionMethod['code']);
                $paymentMethodObj = $this->config->getPaymentMethodByCode($originalPaymentMethod);
                $order->addStatusHistoryComment(__('Pay.: Payment method changed from %1 to %2', ($paymentMethodObj['title'] ?? ''), ($transactionMethod['title'] ?? '')))->save();
                $this->payHelper->logDebug('Follow payment method from ' . ($paymentMethodObj['title'] ?? '') . ' to ' . ($transactionMethod['title'] ?? ''));
            }
        }

        $order->setState(Order::STATE_PROCESSING);
    }

    /**
     * @param Order $order
     * @param array $transactionPaid
     * @param bool $multiShippingOrder
     * @return void
     * @throws \Exception
     */
    private function validateAmount(Order $order, array $transactionPaid, bool $multiShippingOrder)
    {
        $orderAmount = round($order->getGrandTotal(), 2);
        $orderBaseAmount = round($order->getBaseGrandTotal(), 2);

        if (!in_array($orderAmount, $transactionPaid) && !in_array($orderBaseAmount, $transactionPaid) && !$multiShippingOrder) {
            $this->payHelper->logCritical('Amount validation error.', [
                'transactionPaid' => $transactionPaid,
                'orderAmount' => $order->getGrandTotal(),
                'orderBaseAmount' => $order->getBaseGrandTotal()
            ]);
            throw new \Exception('Amount validation error.');
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    private function processCustomerNotification(Order $order)
    {
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
            $order->setEmailSent(true);
            $order->addStatusHistoryComment(__('Pay. - Order confirmation sent'))->setIsCustomerNotified(true);
        }
    }

    /**
     * @param PayOrder $payOrder
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    private function processPayment(PayOrder $payOrder, Order $order)
    {
        $payment = $order->getPayment();

        if ($payOrder->isAuthorized()) {
            if ($this->config->setTotalPaid()) {
                $this->payHelper->logDebug('Set total-paid according to setting');
                $order->setTotalPaid($order->getGrandTotal());
            }
            $payment->registerAuthorizationNotification($order->getBaseGrandTotal());
        } else {
            $payOrderPayments = $payOrder->getPayments();
            if ($this->config->registerPartialPayments() && count($payOrderPayments) > 1) {
                foreach ($payOrderPayments as $partialPayment) {
                    $this->addTransaction($partialPayment, $order);
                }
                if ($order->canInvoice()) {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->register()->pay();
                    $this->invoiceRepository->save($invoice);
                    $order->addStatusHistoryComment(__('Pay.: Factuur aangemaakt na volledige deelbetalingen.'));
                }
                $order->setTotalPaid($order->getBaseGrandTotal());
                $payment->setBaseAmountPaid($order->getBaseGrandTotal());
            } else {
                $payment->registerCaptureNotification($order->getBaseGrandTotal(), $this->config->isSkipFraudDetection());
            }
        }
    }

    /**
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    private function sendInvoiceIfNeeded(Order $order)
    {
        $invoice = $order->getPayment()->getCreatedInvoice();
        if ($invoice && !$invoice->getEmailSent()) {
            $this->invoiceSender->send($invoice);
            $order->addStatusHistoryComment(__('Pay. - You notified customer about invoice #%1', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)->save();
        }
    }

    /**
     * @param PayOrder $payOrder
     * @param Order $order
     * @param Interceptor $payment
     * @param $newStatus
     * @return true
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processB2BPayment(PayOrder $payOrder, Order $order, Interceptor $payment, $newStatus)
    {
        # Create transaction
        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
        $transactionMessage = __('Pay. - Captured amount of %1.', $formatedPrice);
        $transactionBuilder = $this->builderInterface->setPayment($payment)->setOrder($order)->setTransactionId($payOrder->getId())->setFailSafe(true)->build('capture');
        $payment->addTransactionCommentsToOrder($transactionBuilder, $transactionMessage);
        $payment->setParentTransactionId(null);
        $payment->save();
        $transactionBuilder->save();

        # Change amount paid manually
        $order->setTotalPaid($order->getGrandTotal());
        $order->setBaseTotalPaid($order->getBaseGrandTotal());
        $order->setStatus(!empty($newStatus) ? $newStatus : Order::STATE_PROCESSING);

        $originSetting = $this->config->invoiceCreation() ? 'B2B' : 'Invoice creation';
        $order->addStatusHistoryComment(__('Pay. - ' . $originSetting . ' setting: Skipped creating invoice'));

        $order->addStatusHistoryComment(__('Pay. - B2B Setting: Skipped creating invoice'));
        $this->orderRepository->save($order);

        return true;
    }

    /**
     * @param array $partialPayment
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    private function addTransaction(array $partialPayment, Order $order)
    {
        $transactionId = $partialPayment['id'] ?? 'id';
        $profileId = $partialPayment['paymentMethod']['id'] ?? '0';
        $orderPayment = $order->getPayment();
        $method = $this->config->getPaymentmethod($profileId) ?? 'empty';
        $methodName = $method['code'] ?? 'empty';

        $amount = ($partialPayment['amount']['value'] ?? 0) / 100;
        $currency = $partialPayment['amount']['currency'] ?? 'EUR';

        $transaction = $this->builderInterface->setPayment($orderPayment)
            ->setOrder($order)
            ->setTransactionId($transactionId)
            ->setFailSafe(true)
            ->build('capture')
            ->setAdditionalInformation(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                [
                    'Paymentmethod' => $methodName,
                    'Amount' => $amount,
                    'Currency' => $currency
                ]
            );

        $transaction->setIsClosed(true)->save();

       $order->addStatusHistoryComment(__('Pay. - Partial payment received: ' . $currency . ' ' . $amount . ' Method: ' . $methodName));
    }

}
