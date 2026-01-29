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
use PayNL\Sdk\Model\Pay\PayOrder;
use PayNL\Sdk\Util\ExchangeResponse;
use PayNL\Sdk\Util\Exchange as PayExchange;
use PayNL\Sdk\Model\Method;
use Throwable;
use Exception;
use Paynl\Payment\Model\PayProcessingRepository;

class Exchange extends PayAction implements CsrfAwareActionInterface
{

    /**
     * @var PayProcessingRepository
     */
    private $payProcessingRepository;

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
     * @var \Paynl\Payment\Helper\PayHelper
     */
    private $payHelper;

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
     * @param PayProcessingRepository $payProcessingRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Framework\Controller\Result\Raw $result,
        OrderRepository $orderRepository,
        PayPayment $payPayment,
        CreateFastCheckoutOrder $createFastCheckoutOrder,
        PayHelper $payHelper,
        PayProcessingRepository $payProcessingRepository
    ) {
        $this->result = $result;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->payPayment = $payPayment;
        $this->createFastCheckoutOrder = $createFastCheckoutOrder;
        $this->payHelper = $payHelper;
        $this->payProcessingRepository = $payProcessingRepository;
        parent::__construct($context);
    }

    /**
     * @param string $entityId
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    private function getOrder(string $entityId)
    {
        try {
            $order = $this->orderRepository->get($entityId);
        } catch (\Exception $e) {
            $order = null;
        }
        return $order;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $exchange = new PayExchange($this->getRequest()->getParams());
        $exchange->setGmsReferenceKey('extra3');

        try {
            $payOrderId = $exchange->getPayOrderId();
            $action = $exchange->getAction();
            $extra3 = $exchange->getPayLoad()->getExtra3();

            if ($action == 'pending') {
                return $this->result->setContents($exchange->setResponse(true, 'Ignoring pending'));
            }

            if ($exchange->eventPaid(true)) {
                if ($this->payProcessingRepository->existsEntry($payOrderId, 'processing')) {
                    return $this->result->setContents($exchange->setResponse(false, 'Order already processing'));
                }
                $this->payProcessingRepository->createEntry($payOrderId, 'processing');
            }

            # We need to load the order to get the credentials matching the store/storeview
            $order = $this->getOrder($extra3);

            # Retrieve credentials
            if (!empty($order)) { $this->config->setStore($order->getStore()); }

            # Now process this exchange request...
            $payOrder = $exchange->process($this->config->getPayConfig());
            $order = $this->getOrder($payOrder->getExtra3());

            if ($payOrder->isPending()) {
                $eResponse = new ExchangeResponse(true, 'Ignoring pending state');

            } elseif ($payOrder->isRefunded() && $exchange->eventRefund()) {
                $eResponse = $this->processRefund($order);

            } elseif ($payOrder->isChargeBack() && $exchange->eventChargeBack()) {
                $eResponse = $this->processChargeback($order);

            } elseif ($payOrder->isPaid() || $payOrder->isAuthorized())
            {
                # Create order on fastcheckout
                if ($exchange->isFastCheckout() && $exchange->eventPaid() && empty($order)) {
                    $order = $this->createFastCheckoutOrder->create($exchange->getPayLoad());
                }

                # If exchange is triggered by internal capturing, ignore processing to avoid deadlock and finish capture event.
                if ($this->payProcessingRepository->existsEntry($payOrderId, 'queue_capture')) {
                    $this->payProcessingRepository->deleteEntry($payOrderId, 'queue_capture')->deleteEntry($payOrderId, 'processing');
                    $eResponse = new ExchangeResponse(true, 'capture processed');

                # Retourpin - doesnt need to be -processPaid-, only set the order to refunded
                } elseif (Method::RETOURPIN == $payOrder->getPaymentMethod() && $exchange->eventPaid(true)) {
                    $eResponse = $this->processRetourPin($payOrder->getExtra3());

                } else {
                    $eResponse = $this->processPaid($payOrder, $order);
                }

            } elseif ($payOrder->isVoided()) {
                $eResponse = $this->processVoid($order, $payOrder);

            } elseif ($payOrder->isCancelled() || $payOrder->isDenied()) {
                $eResponse = $this->processCancel($order, $payOrder);

            } elseif ($payOrder->isBeingVerified()) {
                $eResponse = new ExchangeResponse(true, 'Ignoring verified');

            } else {
                $eResponse = new ExchangeResponse(true, 'No action defined for payment state ' . $payOrder->getStatusCode());
            }

        } catch (Throwable $exception) {
            $eResponse = new ExchangeResponse(
                $exception->getCode() !== PayExchange::ERROR_ACTION_FAULT,
                'Exception message: ' . $exception->getMessage()
            );
        }

        try {
            $this->removeProcessing($payOrderId ?? '', $exchange->eventPaid());
        } catch (Throwable $exception) {
            $this->payHelper->logDebug('Exception removing processing: ' . $exception->getMessage());
        }

        return $this->result->setContents($exchange->setExchangeResponse($eResponse, true));
    }

    /**
     * @param PayOrder $payOrder
     * @param Order $order
     * @return ExchangeResponse
     */
    private function processPaid(PayOrder $payOrder, Order $order): ExchangeResponse
    {
        $result = false;
        try {
            if ($order->getState() === Order::STATE_CLOSED && $order->getPayment()->getAmountRefunded() > 0) {
                $result = true;
                throw new Exception('Cant process. Order is already fully refunded');
            }

            if ($order->getTotalDue() <= 0) {
                $result = true;
                throw new Exception('Already processed order ' . $order->getId());
            }

            if ($order->getState() === Order::STATE_PROCESSING) {
                if ($order->canInvoice()) {
                    if ($payOrder->isAuthorized()) {
                        # If here, then there's no need to continue, state is processing,
                        throw new Exception('Already state_processing order authorsed order ' . $order->getId());
                    } else {
                        $alreadyProcessed = false;
                        foreach ($order->getInvoiceCollection() as $invoice) {
                            if ($invoice->getTransactionId() === $payOrder->getOrderId()) {
                                $alreadyProcessed = true;
                                break;
                            }
                        }
                        if ($alreadyProcessed) {
                            return new ExchangeResponse(true, 'Already captured. ' . $order->getId());
                        }
                    }
                } else {
                    return new ExchangeResponse(true, 'Already captured');
                }
            }

            $result = $this->payPayment->processPaidOrder($payOrder, $order, $payOrder->getPaymentMethod());
            if (!$result) {
                throw new Exception('Could not process order');
            }

            $message =  $payOrder->isPaid() ? "PAID" : "AUTHORIZED";
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->payHelper->logDebug('Exception processPaid: ' . $message);
        }
        return new ExchangeResponse($result ?? false, $message);
    }

    /**
     * @param Order $order
     * @return ExchangeResponse
     */
    private function processChargeback($order): ExchangeResponse
    {
        $result = true;
        $message = 'Chargeback success';

        try {
            $response = $this->payPayment->chargebackOrder($order);
            if ($response !== true) {
                throw new Exception('Could not process chargeback');
            }
        } catch (Exception $e) {
            $result = false;
            $message = $e->getMessage();
        }

        return new ExchangeResponse($result, $message);
    }

    /**
     * @param $orderEntityId
     * @return ExchangeResponse
     */
    private function processRetourPin($orderEntityId): ExchangeResponse
    {
        $message = 'Refund by card success';
        $response = false;

        try {
            $response = $this->payPayment->cardRefundOrder($orderEntityId);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        return new ExchangeResponse($response === true, $message);
    }

    /**
     * @param $order
     * @return ExchangeResponse
     */
    private function processRefund($order): ExchangeResponse
    {
        $result = true;
        $message = 'Refund success';

        if ($this->config->refundFromPay()) {
            if ($order->getBaseTotalRefunded() == $order->getBaseGrandTotal()) {
                $message = 'Already refunded';
            } else {
                try {
                    $response = $this->payPayment->refundOrder($order->getId());
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $result = false;
                }
            }
        } else {
            $message = 'Ignoring: Refund processing initiated by Pay disabled via plugin settings.';
        }
        return new ExchangeResponse($result, $message);
    }

    /**
     * @param Order|null $order
     * @param PayOrder $payOrder
     * @return ExchangeResponse
     */
    private function processVoid(?Order $order, PayOrder $payOrder): ExchangeResponse
    {
        $result = ['result' => false];
        $message = '';

        foreach ($this->payProcessingRepository->getEntriesByType('queue_void') as $payOrderId) {
            try {
                $queuedOrder = $this->orderRepository->get($payOrderId);
                if ($queuedOrder->canInvoice()) {
                    $result = $this->cancelOrder($queuedOrder);
                    $message .= 'Voided order `' . $payOrderId . '` | ';
                } else {
                    $result = ['result' => true];
                    $message .= 'Already voided order `' . $payOrderId . '` | ';
                }
            } catch (Exception $e) {
                $message .= $e->getMessage() . ' | ';
                $queuedOrder = null;
            }

            $this->payProcessingRepository->deleteEntry($payOrderId, 'queue_void');
        }

        if (strlen($message) > 0 && substr($message, -2, 2) === '| ') {
            $message = substr($message, 0, -2);
        }

        if ($order !== null && $order->canInvoice()) {
            // Order is authorized but not captured — queue to avoid deadlock
            $this->payProcessingRepository->createEntry($order->getId(), 'queue_void');
            return new ExchangeResponse(false, 'Void queued for ' . $order->getId() . ' ' . $message);
        }

        return new ExchangeResponse(true, empty($message) ? 'Void processed' : $message);
    }


    /**
     * @param Order|null $order
     * @param PayOrder $payOrder
     * @return ExchangeResponse
     */
    private function processCancel(?Order $order, PayOrder $payOrder): ExchangeResponse
    {
        if ($order === null) {
            return new ExchangeResponse(true, 'Cancel skipped. Order is null');
        }

        $result = ['result' => false];

        if ($order->getState() === Order::STATE_PROCESSING) {
            $message = 'Cancel ignored. Order is `processing`';
        } elseif ($order->getState() === Order::STATE_CLOSED && $order->getPayment()->getAmountRefunded() > 0) {
            $message = 'Cancel ignored. Order is refunded';
        } else {
            if ($this->config->isNeverCancel()) {
                $message = "Not Canceled because option `never-cancel-order` is enabled";
            } else {
                $result = $this->cancelOrder($order);
                $message = $result['message'];
            }
        }

        return new ExchangeResponse($result['result'] ?? true, $message);
    }


    /**
     * @param $order
     * @return array
     */
    private function cancelOrder($order)
    {
        $result = true;
        try {
            if ($order->iscanceled()) {
                throw new Exception('Order already cancelled');
            }

            $order->setData('is_manual_cancel', true)->save();
            $result = $this->payPayment->cancelOrder($order);

            if (empty($result)) {
                throw new Exception('Cannot cancel order');
            }
            $message = 'CANCELED';
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        return compact('result', 'message');
    }

    /**
     * @param string $payOrderId
     * @param bool $eventPaid
     * @return void
     */
    private function removeProcessing(string $payOrderId, bool $eventPaid)
    {
        if ($eventPaid) {
            $this->payProcessingRepository->deleteEntry($payOrderId, 'processing');
        }
    }

}
