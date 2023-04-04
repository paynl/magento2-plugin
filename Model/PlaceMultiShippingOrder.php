<?php

namespace Paynl\Payment\Model;

use Magento\Framework\App\Helper\Context;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Model\CheckoutUrl;
use Paynl\Payment\Model\PayPaymentCreate;
use Paynl\Error\Error;
use Paynl\Payment\Helper\PayHelper;

class PlaceMultiShippingOrder implements PlaceOrderInterface
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var array
     */
    private $errorList = [];

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var PayHelper
     */
    private $paynlHelper;

    /**
     * @var CheckoutUrl
     */
    private $checkoutUrl;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param OrderManagementInterface $orderManagement
     * @param PayHelper $paynlHelper
     * @param \Paynl\Payment\Model\CheckoutUrl $checkoutUrl
     * @param PaymentHelper $paymentHelper
     * @param Context $context
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        PayHelper $paynlHelper,
        CheckoutUrl $checkoutUrl,
        PaymentHelper $paymentHelper,
        Context $context,
        OrderRepository $orderRepository
    ) {
        $this->orderManagement = $orderManagement;
        $this->paynlHelper = $paynlHelper;
        $this->checkoutUrl = $checkoutUrl;
        $this->paymentHelper   = $paymentHelper;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param OrderInterface[] $orderList
     * @return array
     */
    public function place(array $orderList): array
    {
        $totalOrderAmount = 0;

        try {
            foreach ($orderList as $order) {
                $this->orderManagement->place($order);
                $totalOrderAmount += $order->getBaseGrandTotal();
            }

            $jsonOrders = json_encode($this->getOrderIds($orderList));
            foreach ($orderList as $order) {
                $order->getPayment()->setAdditionalInformation('order_ids', $jsonOrders)->save();
            }

            $totalOrderAmount = number_format($totalOrderAmount ?? 0.0, 2, '.', '');
            $order = reset($orderList);
            $payment = $order->getPayment();
            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());

            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                payHelper::logNotice('Start new payment for multishipping order ' . $order->getId(), array(), $order->getStore());
                $transaction = $methodInstance->startMultiShippingOrder($order, $totalOrderAmount, $this->getRedirectUrl($orderList));
                $order->getPayment()->setAdditionalInformation('transactionId', $transaction->getTransactionId())->save();
                $this->checkoutUrl->setUrl($transaction->getRedirectUrl());
            } else {
                throw new Error('PAY.: Method is not a paynl payment method');
            }
        } catch (\Exception $exception) {
            $errorList = [];
            foreach ($orderList as $order) {
                $errorList[$order->getIncrementId()] = $exception;
            }
            return $errorList;
        }

        return $this->errorList;
    }

    /**
     * @param array $orders
     * @return array
     */
    private function getOrderIds(array $orders): array
    {
        return array_map(function (OrderInterface $order) {
            return $order->getId();
        }, $orders);
    }

    /**
     * @param array $orders
     * @return string
     * @throws \Exception
     */
    private function getRedirectUrl(array $orders): string
    {
        if (!$orders) {
            throw new \Exception('The provided order array is empty');
        }

        $order = reset($orders);
        $parameters = http_build_query([
            'order_ids' => $this->getOrderIds($orders),
            'entityid' => $order->getEntityId()
        ]);

        $this->urlBuilder->setScope($order->getStoreId());
        return $this->urlBuilder->getUrl('paynl/checkout/finish/', ['_query' => $parameters]);
    }
}
