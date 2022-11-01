<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPayment;
use Paynl\Transaction;
use Paynl\Config as PAYSDK;

/**
 * Communicates with PAY. in order to update payment statuses in magento
 *
 * @author Andy Pieters <andy@pay.nl>
 */
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
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $paynlHelper;

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
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        PayPayment $payPayment,
        PayHelper $paynlHelper
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payPayment = $payPayment;
        $this->paynlHelper = $paynlHelper;

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

        if ($action == 'new_ppt') {
            if ($this->paynlHelper->checkProcessing($payOrderId)) {
                return $this->result->setContents('FALSE| Order already processing.');
            }
        }

        $this->config->setStore($order->getStore());
        PAYSDK::setApiToken($this->config->getApiToken());

        try {
            $transaction = Transaction::get($payOrderId);
        } catch (\Exception $e) {
            payHelper::logCritical($e, $params, $order->getStore());
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

        if ($orderEntityId != $orderEntityIdTransaction) {
            payHelper::logCritical('Transaction mismatch ' . $orderEntityId . ' / ' . $orderEntityIdTransaction, $params, $order->getStore());
            $this->removeProcessing($payOrderId, $action);
            return $this->result->setContents('FALSE|Transaction mismatch');
        }

        if ($order->getTotalDue() <= 0) {
            payHelper::logDebug($action . '. Ignoring - already paid: ' . $orderEntityId);
            if (!$this->config->registerPartialPayments()) {
                $this->removeProcessing($payOrderId, $action);
                return $this->result->setContents('TRUE| Ignoring: order has already been paid');
            }
        }

        if ($action == 'capture') {
            if (!empty($payment) && $payment->getAdditionalInformation('manual_capture')) {
                payHelper::logDebug('Already captured.');
                return $this->result->setContents('TRUE| Already captured.');
            }
            if ($this->config->autoCaptureEnabled()) {
                return $this->result->setContents('TRUE| CAPTURED');
            }
        }

        if ($transaction->isPaid() || $transaction->isAuthorized()) {
            try {
                $result = $this->payPayment->processPaidOrder($transaction, $order);
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
     * @param $payOrderId
     * @param $action
     */
    private function removeProcessing($payOrderId, $action)
    {
        if ($action == 'new_ppt') {
            $this->paynlHelper->removeProcessing($payOrderId);
        }
    }
}
