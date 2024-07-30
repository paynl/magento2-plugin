<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPayment;
use Paynl\Payment\Model\PayPaymentCreateFastCheckoutOrder;
use Paynl\Transaction;

class Exchange extends PayAction implements CsrfAwareActionInterface
{
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
     * @var PayPaymentCreateFastCheckoutOrder
     */
    private $payPaymentCreateFastCheckoutOrder;

    /**
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @var
     */
    private $headers;

    /**
     *
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
     * @param PayPaymentCreateFastCheckoutOrder $payPaymentCreateFastCheckoutOrder
     * @param PayHelper $payHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        PayPayment $payPayment,
        PayPaymentCreateFastCheckoutOrder $payPaymentCreateFastCheckoutOrder,
        PayHelper $payHelper
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payPayment = $payPayment;
        $this->payPaymentCreateFastCheckoutOrder = $payPaymentCreateFastCheckoutOrder;
        $this->payHelper = $payHelper;
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
     * @throws Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function getPayLoad($_request)
    {
        $request = (object) $_request->getParams() ?? null;

        $action = $request->action ?? null;
        if (!empty($action)) {
            # The argument "action" tells us this is not coming from TGU. Todo: check should be better
            $action = $request->action ?? null;
            $paymentProfile = $request->payment_profile_id ?? null;
            $payOrderId = $request->order_id ?? null;
            $orderId = $request->extra1 ?? null;
            $data = null;
        } else {
            # TGU
            if ($_request->isGet() || !$this->isSignExchange()) {
                $data['object'] = $request->object ?? null;
            } else {
                $rawBody = file_get_contents('php://input');
                $data = json_decode($rawBody, true, 512, 4194304);
                $exchangeType = $data['type'] ?? null;

                # Volgens documentatie alleen type order verwerken. https://developer.pay.nl/docs/signing
                if ($exchangeType != 'order') {
                    throw new Exception('Cant handle exchange type other then order');
                }
            }
            $this->payHelper->logDebug('payload', $data);
            $payOrderId = $data['object']['orderId'] ?? '';
            $internalStateId = $data['object']['status']['code'] ?? '';
            $internalStateName = $data['object']['status']['action'] ?? '';
            $orderId = $data['object']['reference'] ?? '';
            $action = ($internalStateId == 100 || $internalStateName == 95) ? 'new_ppt' : 'pending';
            $checkoutData = $data['object']['checkoutData'] ?? '';
        }

        // Return mapped data so it works for all type of exchanges.
        return [
            'action' => $action,
            'paymentProfile' => $paymentProfile ?? null,
            'payOrderId' => $payOrderId,
            'orderId' => $orderId,
            'internalStateId' => $internalStateId ?? null,
            'internalStateName' => $internalStateName ?? null,
            'checkoutData' => $checkoutData ?? null,
            'orgData' => $data,
        ];
    }

    /**
     * @param array $params
     * @return boolean
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function isFastCheckout($params)
    {
        return strpos($params['orderId'], "fastcheckout") !== false && !empty($params['checkoutData'] ?? '');
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $params = $this->getPayLoad($this->getRequest());
        $action = strtolower($params['action'] ?? '');
        $payOrderId = $params['payOrderId'] ?? null;
        $orderEntityId = $params['orderId'] ?? null;
        $paymentProfileId = $params['paymentProfile'] ?? null;
        $order = null;

        if ($action == 'pending') {
            return $this->result->setContents('TRUE| Ignore pending');
        }

        if ($this->isFastCheckout($params)) {
            try {
                $order = $this->payPaymentCreateFastCheckoutOrder->create($params);
            } catch (\Exception $e) {
                $this->payHelper->logCritical($e, $params);
                return $this->result->setContents('FALSE| Error creating fast checkout order. ' . $e->getMessage());
            }
        }

        if (empty($payOrderId) || empty($orderEntityId)) {
            $this->payHelper->logCritical('Exchange: order_id or orderEntity is not set', $params);
            return $this->result->setContents('FALSE| order_id is not set in the request');
        }

        if (empty($order)) {
            try {
                $order = $this->orderRepository->get($orderEntityId);
                if (empty($order)) {
                    $this->payHelper->logCritical('Cannot load order: ' . $orderEntityId);
                    throw new \Exception('Cannot load order: ' . $orderEntityId);
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

        $this->config->setStore($order->getStore());

        try {
            $this->config->configureSDK(true);
            $transaction = Transaction::get($payOrderId);
        } catch (\Exception $e) {
            $this->payHelper->logCritical($e, $params, $order->getStore());
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

        if (method_exists($transaction, 'isPartialPayment')) {
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
            if ($this->config->refundFromPay() && $order->getTotalDue() == 0) {
                if ($order->getBaseTotalRefunded() == $order->getBaseGrandTotal()) {
                    return $this->result->setContents('TRUE|Already fully refunded');
                }
                try {
                    $response = $this->payPayment->refundOrder($orderEntityId);
                } catch (Exception $e) {
                    $response = $e->getMessage();
                }
                return $this->result->setContents($response === true ? 'TRUE|Refund success' : 'FALSE|' . $response);
            }
        }

        if ($transaction->isChargeBack() && substr($action, 0, 10) == 'chargeback') {
            try {
                $response = $this->payPayment->chargebackOrder($orderEntityId);
            } catch (Exception $e) {
                $response = $e->getMessage();
            }
            return $this->result->setContents($response === true ? 'TRUE|Chargeback success' : 'FALSE|' . $response);
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
