<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;

/**
 * Finishes up the payment and redirects the user to the thank you page.
 *
 * @author PAY. <webshop@pay.nl>
 */
class Finish extends PayAction
{
    /**
     *
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param Config $config
     * @param Session $checkoutSession
     * @param OrderRepository $orderRepository
     * @param QuoteRepository $quoteRepository
     * @param PayHelper $payHelper
     * @param ManagerInterface $eventManager
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        PayHelper $payHelper,
        ManagerInterface $eventManager,
        QuoteFactory $quoteFactory
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->payHelper = $payHelper;
        $this->eventManager = $eventManager;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context);
    }

    /**
     * Check if session is active.
     * @param Order $order
     * @param string $orderId
     * @param Session $session
     * @param boolean $pickupMode
     * @return void
     */
    private function checkSession(Order $order, string $orderId, Session $session, $pickupMode = null)
    {
        if ($session->getLastOrderId() != $order->getId()) {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            $transactionId = (isset($additionalInformation['transactionId'])) ? $additionalInformation['transactionId'] : null;

            if ($orderId == $transactionId || !empty($pickupMode)) {
                $this->checkoutSession->setLastQuoteId($order->getQuoteId())
                    ->setLastSuccessQuoteId($order->getQuoteId())
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId());
            }
        }
    }

    /**
     * Check if field is empty.
     * @param mixed $field
     * @param string $name
     * @param integer $errorCode
     * @param string|null $desc
     * @throws \Exception
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @return void
     */
    private function checkEmpty($field, string $name, int $errorCode, string $desc = null)
    {
        if (empty($field)) {
            $desc = empty($desc) ? $name . ' is empty' : $desc;
            throw new \Exception('Finish: ' . $desc, $errorCode);
        }
    }

    /**
     * @return resultRedirectFactory|void
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getRequest()->getParams();
        $payOrderId = empty($params['orderId']) ? (empty($params['orderid']) ? null : $params['orderid']) : $params['orderId'];
        $orderStatusId = empty($params['orderStatusId']) ? null : (int)$params['orderStatusId'];
        $magOrderId = empty($params['entityid']) ? null : $params['entityid'];
        $orderIds = empty($params['order_ids']) ? null : $params['order_ids'];
        $pickupMode = !empty($params['pickup']);
        $bSuccess = $orderStatusId === Config::ORDERSTATUS_PAID;
        $bPending = in_array($orderStatusId, Config::ORDERSTATUS_PENDING);
        $bDenied = $orderStatusId === Config::ORDERSTATUS_DENIED;
        $bCanceled = $orderStatusId === Config::ORDERSTATUS_CANCELED;
        $bVerify = $orderStatusId === Config::ORDERSTATUS_VERIFY;
        $isPinTransaction = false;
        $multiShipFinish = is_array($orderIds);

        try {
            if ($pickupMode) {
                $order = $this->orderRepository->get($magOrderId);
                $payOrderId = '';
                $this->deactivateCart($order, $payOrderId, true);
                $successUrl = Config::FINISH_PICKUP;
                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
                return $resultRedirect;
            }

            $this->checkEmpty($payOrderId, 'payOrderid', 101);
            $this->checkEmpty($magOrderId, 'magOrderId', 1012);

            $order = $this->orderRepository->get($magOrderId);
            $this->checkEmpty($order, 'order', 1013);

            $this->config->setStore($order->getStore());

            $payment = $order->getPayment();
            $information = $payment->getAdditionalInformation();

            $this->checkEmpty(($information['transactionId'] ?? null) == $payOrderId, '', 1014, 'transaction mismatch');

            if (!empty($information['terminal_hash']) && !$bSuccess) {
                $isPinTransaction = true;
                $pinStatus = $this->handlePin($information['terminal_hash'], $order);
                if (!empty($pinStatus)) {
                    $bSuccess = true;
                } else {
                    $bPending = false;
                }
            }

            if (empty($bSuccess) && !$isPinTransaction) {
                $this->config->configureSDK();
                $transaction = \Paynl\Transaction::get($payOrderId);
                $orderNumber = $transaction->getExtra1();
                $this->checkEmpty($order->getIncrementId() == $orderNumber, '', 104, 'order mismatch');
                $bSuccess = ($transaction->isPaid() || $transaction->isAuthorized());
            }

            if ($bSuccess || $bVerify) {
                $successUrl = $this->config->getSuccessPage($payment->getMethod());
                if (empty($successUrl)) {
                    $successUrl = ($payment->getMethod() == 'paynl_payment_paylink' || $this->config->sendEcommerceAnalytics()) ? Config::FINISH_PAY : Config::FINISH_STANDARD;
                }
                $this->payHelper->logDebug('Finish succes', [$successUrl, $payOrderId, $bSuccess, $bVerify]);
                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
                if ($isPinTransaction && $pinStatus->getTransactionState() !== 'approved') {
                    $this->messageManager->addNoticeMessage(__('Order has been placed and payment is pending'));
                }
                if ($bVerify) {
                    $order->addStatusHistoryComment(__('Pay. - this payment has been flagged as possibly fraudulent. Please verify this transaction in My.pay.nl.'));
                    $this->orderRepository->save($order);
                }
                if ($multiShipFinish) {
                    $this->eventManager->dispatch('pay_multishipping_success_redirect', [
                        'order_ids' => $orderIds,
                        'request' => $this->getRequest(),
                        'response' => $this->getResponse(),
                    ]);
                    return;
                }
                $this->deactivateCart($order, $payOrderId);
            } elseif ($bPending) {
                $successUrl = Config::FINISH_STANDARD;
                if ($this->config->getPendingPage()) {
                    $successUrl = Config::PENDING_PAY;
                } elseif ($this->config->sendEcommerceAnalytics()) {
                    $successUrl = Config::FINISH_PAY;
                }
                $this->payHelper->logDebug('Finish succes', [$successUrl, $payOrderId]);
                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
                $this->deactivateCart($order, $payOrderId);
            } else {
                $cancelMessage = $bDenied ? __('Payment denied') : __('Payment cancelled');
                $this->messageManager->addNoticeMessage($cancelMessage);
                $this->reactivateCart($order);
                if ($multiShipFinish) {
                    $session = $this->checkoutSession;
                    $sessionId = $session->getLastQuoteId();
                    $quote = $this->quoteFactory->create()->loadByIdWithoutStore($sessionId);
                    if (!empty($quote->getId())) {
                        $quote->setIsActive(true)->setReservedOrderId(null)->save();
                        $session->replaceQuote($quote);
                    }
                }
                $cancelUrl = $payment->getMethod() == 'paynl_payment_paylink' ? Config::CANCEL_PAY : $this->config->getCancelURL();
                $this->payHelper->logDebug('Finish cancel/denied. Message: ' . $cancelMessage, [$multiShipFinish, $payOrderId, $cancelUrl]);
                $resultRedirect->setPath($cancelUrl);
            }
        } catch (\Exception $e) {
            $this->payHelper->logCritical($e->getCode() . ': ' . $e->getMessage(), $params);

            if ($e->getCode() == 101) {
                $this->messageManager->addNoticeMessage(__('Invalid return, no transactionId specified'));
            } else {
                $this->messageManager->addNoticeMessage(__('Unfortunately something went wrong'));
            }
            $this->reactivateCart($order);
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }

    /**
     * @param string $hash
     * @param Order $order
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return \Paynl\Instore::status
     */
    private function handlePin(string $hash, Order $order)
    {
        $this->config->configureSDK(true);
        $status = \Paynl\Instore::status(['hash' => $hash]);
        if (in_array($status->getTransactionState(), ['cancelled', 'expired', 'error'])) {
            # Instore does not send a canceled exchange message, so cancel it here
            $order->cancel();
            $this->orderRepository->save($order);
            return false;
        }
        return $status;
    }

    /**
     * @param Order $order
     * @param string $payOrderId
     * @param boolean $pickupMode
     * @return void
     */
    private function deactivateCart(Order $order, string $payOrderId, $pickupMode = null)
    {
        # Make the cart inactive
        $session = $this->checkoutSession;

        $this->checkSession($order, $payOrderId, $session, $pickupMode);

        $quote = $session->getQuote();
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
    }

    /**
     * @param Order $order
     * @return void
     */
    private function reactivateCart(Order $order)
    {
        # Make the cart active
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(true)->setReservedOrderId(null);
        $this->checkoutSession->replaceQuote($quote);
        $this->quoteRepository->save($quote);
    }
}
