<?php

namespace Paynl\Payment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Interceptor;
use Magento\Sales\Model\OrderRepository;
use Magento\SalesRule\Model\Coupon\UpdateCouponUsages;
use Paynl\Result\Transaction\Transaction as PayTransaction;
use Paynl\Payment\Helper\PayHelper;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;

class PayPayment
{
    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     *
     * @var Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $builderInterface;

    /**
     *
     * @var Magento\Sales\Model\Order\PaymentFactory
     */
    private $paymentFactory;

    /**
     *
     * @var Magento\SalesRule\Model\Coupon\UpdateCouponUsages
     */
    private $updateCouponUsages;

    private $paynlConfig;

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
     * @param \Paynl\Payment\Model\Config $paynlConfig
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface
     * @param \Magento\Sales\Model\Order\PaymentFactory $paymentFactory
     * @param UpdateCouponUsages $updateCouponUsages
     * @param PayHelper $payHelper
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $cmFac
     * @param \Magento\Sales\Model\Service\CreditmemoService $cmService
     */
    public function __construct(
        \Paynl\Payment\Model\Config $config,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        OrderRepository $orderRepository,
        \Paynl\Payment\Model\Config $paynlConfig,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builderInterface,
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory,
        UpdateCouponUsages $updateCouponUsages,
        PayHelper $payHelper,
        \Magento\Sales\Model\Order\CreditmemoFactory $cmFac,
        \Magento\Sales\Model\Service\CreditmemoService $cmService
    ) {
        $this->eventManager = $eventManager;
        $this->config = $config;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->paynlConfig = $paynlConfig;
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
            $order->cancel();
            $order->addStatusHistoryComment(__('PAY. - Canceled the order'));
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
        $order->addStatusHistoryComment(__('PAY. - Uncanceled order'), false);
        if (!empty($order->getCouponCode())) {
            $this->updateCouponUsages->execute($order, true);
        }
        $this->eventManager->dispatch('order_uncancel_after', ['order' => $order]);
    }

    /**
     * @param int $orderEntityId
     * @return true
     * @throws \Exception
     */
    public function refundOrder($orderEntityId)
    {
        try {
            $order = $this->orderRepository->get($orderEntityId);
            $creditmemo = $this->cmFac->createByOrder($order);
            $this->cmService->refund($creditmemo);

            $order->addStatusHistoryComment(__('PAY. - Refund initiated from Pay.'))->save();
        } catch (\Exception $e) {
            throw new \Exception('Could not refund');
        }
        return true;
    }

    /**
     * @param PayTransaction $transaction
     * @param Order $order
     * @return boolean
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processPaidOrder(PayTransaction $transaction, Order $order)
    {
        $returnResult = false;
        $multiShippingOrder = false;
        $orderProcessList = [];

        # Before processing the payment, check if we should uncancel the corresponding order
        if ($order->isCanceled() || $order->getTotalCanceled() == $order->getGrandTotal()) {
            $this->uncancelOrder($order);
        }

        $paymentMethod = $order->getPayment()->getMethod();
        $newStatus = ($transaction->isAuthorized()) ? $this->config->getAuthorizedStatus($paymentMethod) : $this->config->getPaidStatus($paymentMethod);
        $order_ids = $order->getPayment()->getAdditionalInformation('order_ids');

        if (!empty($order_ids)) {
            $orderids = json_decode($order_ids, true);
            $multiShippingOrder = true;
            foreach ($orderids as $orderId) {
                $this->payHelper->logDebug('Multishipping order:', ['orderid: ' => $orderId]);
                $orderProcessList[] = $this->orderRepository->get($orderId);
            }
        } else {
            $orderProcessList[] = $order;
        }

        $transactionPaid = [
            $transaction->getCurrencyAmount(),
            $transaction->getPaidCurrencyAmount(),
            $transaction->getPaidAmount(),
        ];

        foreach ($orderProcessList as $order) {
            $payment = $order->getPayment();
            $payment->setTransactionId($transaction->getId());
            $payment->setPreparedMessage('PAY. - ');
            $payment->setIsTransactionClosed(0);

            $orderAmount = round($order->getGrandTotal(), 2);
            $orderBaseAmount = round($order->getBaseGrandTotal(), 2);
            if (!in_array($orderAmount, $transactionPaid) && !in_array($orderBaseAmount, $transactionPaid) && $multiShippingOrder === false) {
                $this->payHelper->logCritical('Amount validation error.', array($transactionPaid, $orderAmount, $order->getGrandTotal(), $order->getBaseGrandTotal()));
                throw new \Exception('Amount validation error. Amounts: ' . print_r(array($transactionPaid, $orderAmount, $order->getGrandTotal(), $order->getBaseGrandTotal()), true));
            }

            # Force order state to processing
            $order->setState(Order::STATE_PROCESSING);

            # Notify customer
            if ($order && !$order->getEmailSent()) {
                $this->orderSender->send($order);
                $order->addStatusHistoryComment(__('PAY. - New order email sent'))->setIsCustomerNotified(true)->save();
            }

            # Skip creation of invoice for B2B if enabled
            if ($this->config->ignoreB2BInvoice($paymentMethod) && !empty($order->getBillingAddress()->getCompany())) {
                $returnResult = $this->processB2BPayment($transaction, $order, $payment);
            } else {
                if ($transaction->isAuthorized()) {
                    $payment->registerAuthorizationNotification($order->getBaseGrandTotal());
                } else {
                    $payment->registerCaptureNotification($order->getBaseGrandTotal(), $this->config->isSkipFraudDetection());
                }

                $order->setStatus(!empty($newStatus) ? $newStatus : Order::STATE_PROCESSING);

                $this->orderRepository->save($order);

                $invoice = $payment->getCreatedInvoice();
                if ($invoice && !$invoice->getEmailSent()) {
                    $this->invoiceSender->send($invoice);
                    $order->addStatusHistoryComment(__('PAY. - You notified customer about invoice #%1.', $invoice->getIncrementId()))->setIsCustomerNotified(true)->save();
                }

                $returnResult = true;
            }
        }

        return $returnResult;
    }

    /**
     * @param PayTransaction $transaction
     * @param Order $order
     * @param Interceptor $payment
     * @return boolean
     */
    private function processB2BPayment(PayTransaction $transaction, Order $order, Interceptor $payment)
    {
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

        return true;
    }

    /**
     * @param Order $order
     * @param string $payOrderId
     * @return boolean
     */
    public function processPartiallyPaidOrder(Order $order, string $payOrderId)
    {
        $returnResult = false;
        try {
            $this->config->configureSDK();
            $details = \Paynl\Transaction::details($payOrderId);

            $paymentDetails = $details->getPaymentDetails();
            $transactionDetails = $paymentDetails['transactionDetails'];
            $firstPayment = count($transactionDetails) == 1;
            $totalpaid = 0;
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
                $orderPayment = $this->paymentFactory->create();
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

            $order->addStatusHistoryComment(__('PAY.: Partial payment received: ' . $subProfile . ' - Amount ' . $currency . ' ' . $amount . ' Method: ' . $method));
            $order->setTotalPaid($totalpaid / 100);

            $this->orderRepository->save($order);
            $returnResult = true;
        } catch (\Exception $e) {
            throw new \Exception('Failed processing partial payment: ' . $e->getMessage());
        }

        return $returnResult;
    }
}
