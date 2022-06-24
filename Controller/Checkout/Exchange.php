<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Controller\PayAction;
use \Paynl\Payment\Helper\PayHelper;
use \Paynl\Payment\Model\PayPayment;

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
        PayPayment $payPayment
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payPayment = $payPayment;

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
                    try {
                        $result = $this->payPayment->processPartiallyPaidOrder($order, $payOrderId);
                        if (!$result) {
                            throw new \Exception('Cannot process partial payment');
                        }
                        $message = 'TRUE| Partial payment processed';
                    } catch (\Exception $e) {
                        $message = 'FALSE| ' . $e->getMessage();
                    }
                    return $this->result->setContents($message);
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
            if ($this->config->wuunderAutoCaptureEnabled()) {
                return $this->result->setContents('TRUE| Wuunder already captured.');
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
            return $this->result->setContents($message);
        } elseif ($transaction->isCanceled()) {
            if ($order->getState() == Order::STATE_PROCESSING) {
                return $this->result->setContents("TRUE| Ignoring cancel, order is `processing`");
            } elseif ($order->isCanceled()) {
                return $this->result->setContents("TRUE| Already canceled");
            } else {
                if ($this->config->isNeverCancel()) {
                    return $this->result->setContents("TRUE| Not Canceled because never cancel is enabled");
                }
                try {
                    $this->payPayment->cancelOrder($order);
                    $message = 'TRUE| CANCELED';
                } catch (\Exception $e) {
                    $message = 'FALSE| ' . $e->getMessage();
                }
                return $this->result->setContents($message);
            }
        }
    }
}
