<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\CreateFastCheckoutOrder;
use Paynl\Payment\Model\PayPayment;
use Paynl\Transaction;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Exchange extends PayAction implements CsrfAwareActionInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Controller\Result\Raw
     */
    private $result;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PayPayment
     */
    private $payPayment;

    /**
     * @var CreateFastCheckoutOrder
     */
    private $createFastCheckoutOrder;

    /**
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @var
     */
    private $headers;

    /**
     * @param RequestInterface $request
     * @return null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return boolean
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Framework\Controller\Result\Raw $result
     * @param OrderRepository $orderRepository
     * @param PayPayment $payPayment
     * @param CreateFastCheckoutOrder $createFastCheckoutOrder
     * @param PayHelper $payHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        PayPayment $payPayment,
        CreateFastCheckoutOrder $createFastCheckoutOrder,
        PayHelper $payHelper,
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payPayment = $payPayment;
        $this->createFastCheckoutOrder = $createFastCheckoutOrder;
        $this->payHelper = $payHelper;
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return array|false
     */
    private function getHeaders()
    {
        if (empty($this->headers)) {
            $this->headers = array_change_key_case(getallheaders(), CASE_LOWER);
        }
        return $this->headers;
    }

    /**
     * @return boolean
     */
    private function isSignExchange()
    {
        $headers = $this->getHeaders();
        $signingMethod = $headers['signature-method'] ?? null;
        return $signingMethod === 'HMAC';
    }

    /**
     * @param object $_request
     * @return array
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function getPayLoad($_request)
    {
        $request = (object) $_request->getParams() ?? null;

        $action = $request->action ?? null;
        if (!empty($action)) {
            # The argument "action" tells us this is not TGU.
            $action = $request->action ?? null;
            $paymentProfile = $request->payment_profile_id ?? null;
            $payOrderId = $request->order_id ?? null;
            $orderId = $request->extra1 ?? null;
            $extra3 = $request->extra3 ?? null;
            $extra2 = $request->extra2 ?? null;
            $data = null;
        } else {
            if ($_request->isGet() || !$this->isSignExchange()) {
                $data['object'] = $request->object ?? null;
            } else {
                $rawBody = file_get_contents('php://input');
                $data = json_decode($rawBody, true, 512, 4194304);
                $exchangeType = $data['type'] ?? null;

                # Volgens documentatie alleen type order verwerken. https://developer.pay.nl/docs/signing
                if ($exchangeType != 'order') {
                    throw new \Exception('Cant handle exchange type other then order');
                }
            }

            $payOrderId = $data['object']['orderId'] ?? '';
            $internalStateId = $data['object']['status']['code'] ?? '';
            $internalStateName = $data['object']['status']['action'] ?? '';
            $orderId = $data['object']['reference'] ?? '';
            $extra3 = $data['object']['extra3'] ?? null;
            $extra2 = $data['object']['extra2'] ?? null;
            $action = ($internalStateId == 100 || $internalStateName == 95) ? 'new_ppt' : 'pending';
            $checkoutData = $data['object']['checkoutData'] ?? '';
            $type = $data['object']['type'] ?? '';
        }

        # Return mapped data so it works for all type of exchanges.
        return [
            'action' => $action,
            'paymentProfile' => $paymentProfile ?? null,
            'payOrderId' => $payOrderId,
            'orderId' => $orderId,
            'extra3' => $extra3 ?? null,
            'extra2' => $extra2 ?? null,
            'internalStateId' => $internalStateId ?? null,
            'internalStateName' => $internalStateName ?? null,
            'checkoutData' => $checkoutData ?? null,
            'orgData' => $data,
            'type' => $type ?? null,
        ];
    }

    /**
     * @param array $requestArguments
     * @return bool
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */1
    private function isFastCheckout(array $requestArguments)
    {
        return ($requestArguments['type'] ?? '') == 'payment_based_checkout' && !empty($requestArguments['checkoutData'] ?? '');
    }

    /**
     * @return \Mreturn $this->result->setContents('FALSE| order_id is not set in the request');agento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        try {
            $params = $this->getPayLoad($this->getRequest());
        } catch (\Exception $e) {
            return $this->result->setContents('TRUE| Incorrect payload. ' . $e->getMessage());
        }
        $action = strtolower($params['action'] ?? '');
        $payOrderId = $params['payOrderId'] ?? null;
        $quoteId = $params['orderId'] ?? null;
        $orderEntityId = $params['extra3'] ?? null;
        $paymentProfileId = $params['paymentProfile'] ?? null;
        $order = null;
        $bIsFastCheckout = $this->isFastCheckout($params);

        if ($action == 'pending') {
            return $this->result->setContents('TRUE| Ignore pending');
        }

        if (strpos($params['extra2'] ?? '', 'fastcheckout') !== false) {
            # Disabled fastcheckout related actions.
            return $this->result->setContents('TRUE| Ignoring fastcheckout action ' . $action);
        }

        if (empty($payOrderId)) {
            $this->payHelper->logCritical('Exchange: order_id is not set', $params);
            return $this->result->setContents('FALSE| order_id is not set in the request');
        }

        if ($bIsFastCheckout) {
            # We need the $store to retrieve credentials later on,
            # and with fastcheckout we do this by quote, since there's no order yet.
            $quote = $this->quoteRepository->get($quoteId);
            $store = $this->storeManager->getStore($quote->getStoreId());
        } else {
            try {
                if (empty($orderEntityId)) {
                    throw new \Exception('orderEntityId is not set in the request');
                }
                try {
                    $order = $this->orderRepository->get($orderEntityId);
                } catch (\Exception $e) {
                }

                if (empty($order)) {
                    $quote = $this->quoteRepository->get($orderEntityId);
                    if ($quote->getPayment()->getAdditionalInformation('fastcheckout')) {
                        $this->payHelper->logDebug('loading order from quote');
                        $order = $this->createFastCheckoutOrder->getExistingOrder($orderEntityId);
                    }
                    if (empty($order)) {
                        $this->payHelper->logCritical('Cannot load order: ' . $orderEntityId);
                        throw new \Exception('Cannot load order: ' . $orderEntityId);
                    } else {
                        $this->payHelper->logDebug('order gevonden');
                    }
                }
            } catch (\Exception $e) {
                $this->payHelper->logCritical($e, $params);
                return $this->result->setContents('FALSE| Error loading order. ' . $e->getMessage());
            }
        }

        if ($action == 'new_ppt') {
            if ($this->payHelper->checkProcessing($payOrderId)) {
                return $this->result->setContents('FALSE| Order already processing.');
            }
        }

        $this->config->setStore(empty($store) ? $order->getStore() : $store);

        try {
            if ($bIsFastCheckout) {
                if ($quote->getPayment()->getAdditionalInformation('payOrderId') != $payOrderId) {
                    throw new \Exception("Payment ID mismatch");
                }

                # Retrieve status and customerdata through TGU
                $transaction = $this->payHelper->getTguStatus($payOrderId, $this->config->getTokencode(), $this->config->getApiToken());

                try {
                    # Create the fast checkout order:
                    $order = $this->createFastCheckoutOrder->create($transaction->getCheckoutData(), $quoteId, $payOrderId);
                    $orderEntityId = $order->getId();

                    # Directly processing the payment
                    if ($transaction->isPaid() || $transaction->isAuthorized()) {
                        try {
                            $this->payHelper->logDebug('Fast-checkout processpaid order');
                            $result = $this->payPayment->processPaidOrder($transaction, $order, $paymentProfileId);
                            if (!$result) {
                                throw new \Exception('Cannot process order');
                            }
                            $message = 'TRUE| ' . (($transaction->isPaid()) ? "PAID" : "AUTHORIZED");
                        } catch (\Exception $e) {
                            $message = 'FALSE| ' . $e->getMessage();
                        }
                        return $this->result->setContents($message);
                    } else {
                        $this->payHelper->logDebug('Fast-checkout exchange: TRUE|ignoring fc ' . $action);
                        return $this->result->setContents('TRUE|ignoring fc ' . $action);
                    }

                } catch (\Exception $e) {
                    $this->payHelper->logCritical('Fast checkout: ' . $e->getMessage(), $params);
                    if ($e->getCode() == 404) {
                        return $this->result->setContents('TRUE| Error creating fast checkout order. ' . $e->getMessage());
                    }
                    return $this->result->setContents('FALSE| Error creating fast checkout order. ' . $e->getMessage());
                }
            } else {
                # Default flow
                $this->payHelper->logDebug('default flow');
                $this->config->configureSDK(true);

                $transaction = Transaction::get($payOrderId);
            }
        } catch (\Exception $e) {
            $this->payHelper->logCritical($e, $params);
            $this->removeProcessing($payOrderId, $action);
            return $this->result->setContents('FALSE| Error fetching transaction. ' . $e->getMessage());
        }

        if ($transaction->isPending()) {
            if ($action == 'new_ppt') {
                $this->removeProcessing($payOrderId, $action);
                return $this->result->setContents("FALSE| Payment is pending");
            }
            return $this->result->setContents("TRUE| Ignoring pending");
        }

        if (method_exists($transaction, 'isPartialPayment') && !$bIsFastCheckout) {
            if ($transaction->isPartialPayment()) {
                if ($this->config->registerPartialPayments()) {
                    try {
                        $result = $this->payPayment->processPartiallyPaidOrder($order, $payOrderId);
                        if (!$result) {
                            throw new \Exception('Cannot process partial payment');
                        }
                        $message = 'TRUE| Partial payment processed';
                    } catch (\Exception $e) {
                        $message = 'FALSE| ' . $e->getMessage();
                    }
                    $this->removeProcessing($payOrderId, $action);
                    return $this->result->setContents($message);
                }
                $this->removeProcessing($payOrderId, $action);
                return $this->result->setContents("TRUE| Partial payment");
            }
        }

        $payment = $order->getPayment();
        $orderEntityIdTransaction = $transaction->getExtra3();

        if ($orderEntityId != $orderEntityIdTransaction && !$this->isFastCheckout($params)) {
            $this->payHelper->logCritical('Transaction mismatch ' . $orderEntityId . ' / ' . $orderEntityIdTransaction, $params, $order->getStore());
            $this->removeProcessing($payOrderId, $action);
            return $this->result->setContents('FALSE|Transaction mismatch');
        }

        if ($transaction->isRefunded(false) && substr($action, 0, 6) == 'refund') {
            if ($this->config->refundFromPay()) {
                if ($order->getTotalDue() == 0) {
                    if ($order->getBaseTotalRefunded() == $order->getBaseGrandTotal()) {
                        return $this->result->setContents('TRUE|Already fully refunded');
                    }
                    try {
                        $response = $this->payPayment->refundOrder($order->getEntityId());
                    } catch (\Exception $e) {
                        $response = $e->getMessage();
                    }
                    return $this->result->setContents($response === true ? 'TRUE|Refund success' : 'FALSE|' . $response);
                } else {
                    return $this->result->setContents('TRUE|Ignoring refund, not fully paid');
                }
            } else {
                return $this->result->setContents('TRUE|Ignoring refund, disabled in pluginsettings');
            }
        }

        if ($transaction->isChargeBack() && substr($action, 0, 10) == 'chargeback') {
            try {
                $response = $this->payPayment->chargebackOrder($orderEntityId);
            } catch (\Exception $e) {
                $response = $e->getMessage();
            }
            return $this->result->setContents($response === true ? 'TRUE|Chargeback success' : 'FALSE|' . $response);
        }

        if ($paymentProfileId == '2351' && $action == 'new_ppt') {
            try {
                $response = $this->payPayment->cardRefundOrder($orderEntityId);
            } catch (\Exception $e) {
                $response = $e->getMessage();
            }
            return $this->result->setContents($response === true ? 'TRUE|Refund by card success' : 'TRUE|' . $response);
        }

        if ($order->getTotalDue() <= 0) {
            $this->payHelper->logDebug($action . '. Ignoring - already paid: ' . $orderEntityId);
            if (!$this->config->registerPartialPayments()) {
                $this->removeProcessing($payOrderId, $action);
                return $this->result->setContents('TRUE| Ignoring: order has already been paid');
            }
        }

        if ($action == 'capture') {
            if (!empty($payment) && $payment->getAdditionalInformation('manual_capture')) {
                $this->payHelper->logDebug('Already captured.');
                return $this->result->setContents('TRUE| Already captured.');
            }
            if ($this->config->ignoreManualCapture()) {
                return $this->result->setContents('TRUE| Capture ignored');
            }
        }

        if ($transaction->isPaid() || $transaction->isAuthorized()) {
            try {
                $result = $this->payPayment->processPaidOrder($transaction, $order, $paymentProfileId);
                if (!$result) {
                    throw new \Exception('Cannot process order');
                }

                $message = 'TRUE| ' . (($transaction->isPaid()) ? "PAID" : "AUTHORIZED");
            } catch (\Exception $e) {
                $message = 'FALSE| ' . $e->getMessage();
            }
        } elseif ($transaction->isCanceled()) {
            if ($order->getState() == Order::STATE_PROCESSING) {
                $message = "TRUE| Ignoring cancel, order is `processing`";
            } elseif ($order->isCanceled()) {
                $message = "TRUE| Already canceled";
            } else {
                if ($this->config->isNeverCancel()) {
                    $message = "TRUE| Not Canceled because option `never-cancel-order` is enabled";
                } else {
                    try {
                        $result = $this->payPayment->cancelOrder($order);
                        if (empty($result)) {
                            throw new \Exception('Cannot cancel order');
                        }
                        $message = 'TRUE| CANCELED';
                    } catch (\Exception $e) {
                        $message = 'FALSE| ' . $e->getMessage();
                    }
                }
            }
        }

        $this->removeProcessing($payOrderId, $action);

        return $this->result->setContents($message);
    }

    /**
     * @param string $payOrderId
     * @param string $action
     * @return void
     */
    private function removeProcessing(string $payOrderId, string $action)
    {
        if ($action == 'new_ppt') {
            $this->payHelper->removeProcessing($payOrderId);
        }
    }
}
