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
    private $payHelper;

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
     *
     * @param RequestInterface $request
     * @return boolean
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * Exchange constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Framework\Controller\Result\Raw $result
     * @param OrderRepository $orderRepository
     * @param PayPayment $payPayment
     * @param PayHelper $payHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        PayPayment $payPayment,
        PayHelper $payHelper
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payPayment = $payPayment;
        $this->payHelper = $payHelper;

        parent::__construct($context);
    }

    /**
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
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
            $this->payHelper->logCritical('Exchange: order_id or orderEntity is not set', $params);
            return $this->result->setContents('FALSE| order_id is not set in the request');
        }

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

        if ($orderEntityId != $orderEntityIdTransaction) {
            $this->payHelper->logCritical('Transaction mismatch ' . $orderEntityId . ' / ' . $orderEntityIdTransaction, $params, $order->getStore());
            $this->removeProcessing($payOrderId, $action);
            return $this->result->setContents('FALSE|Transaction mismatch');
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
