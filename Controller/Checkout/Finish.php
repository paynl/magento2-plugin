<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Model\Config;
use Paynl\Payment\Helper\PayHelper;

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
     * Index constructor.
     * @param Context $context
     * @param Config $config
     * @param Session $checkoutSession
     * @param OrderRepository $orderRepository
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(Context $context, Config $config, Session $checkoutSession, OrderRepository $orderRepository, QuoteRepository $quoteRepository)
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    /**
     * Check if session is active.
     * @param Order $order
     * @param string $orderId
     * @param Session $session
     * @return void
     */
    private function checkSession(Order $order, string $orderId, Session $session)
    {
        if ($session->getLastOrderId() != $order->getId()) {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            $transactionId = (isset($additionalInformation['transactionId'])) ? $additionalInformation['transactionId'] : null;
            if ($orderId == $transactionId) {
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
     * @return resultRedirectFactory
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getRequest()->getParams();
        $payOrderId = empty($params['orderId']) ? (empty($params['orderid']) ? null : $params['orderid']) : $params['orderId'];
        $orderStatusId = empty($params['orderStatusId']) ? null : (int)$params['orderStatusId'];
        $magOrderId = empty($params['entityid']) ? null : $params['entityid'];
        $bSuccess = $orderStatusId === Config::ORDERSTATUS_PAID;
        $bPending = in_array($orderStatusId, Config::ORDERSTATUS_PENDING);
        $bDenied = $orderStatusId === Config::ORDERSTATUS_DENIED;
        $bCanceled = $orderStatusId === Config::ORDERSTATUS_CANCELED;
        $bVerify = $orderStatusId === Config::ORDERSTATUS_VERIFY;
        $isPinTransaction = false;

        try {
            $this->checkEmpty($payOrderId, 'payOrderid', 101);
            $this->checkEmpty($magOrderId, 'magOrderId', 1012);

            $order = $this->orderRepository->get($magOrderId);
            $this->checkEmpty($order, 'order', 1013);

            $this->config->setStore($order->getStore());

            $payment = $order->getPayment();
            $information = $payment->getAdditionalInformation();

            $this->checkEmpty($information['transactionId'] == $payOrderId, '', 1014, 'transaction mismatch');

            if (!empty($information['terminal_hash']) && !$bSuccess) {
                $isPinTransaction = true;
                $pinStatus = $this->handlePin($information['terminal_hash'], $order);
                if (!empty($pinStatus)) {
                    $bSuccess = true;
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
                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
                if ($isPinTransaction && $pinStatus->getTransactionState() !== 'approved') {
                    $this->messageManager->addNoticeMessage(__('Order has been made and the payment is pending.'));
                }
                if ($bVerify) {
                    $order->addStatusHistoryComment(__('PAY. - This payment has been flagged as possibly fraudulent. Please verify this transaction in the Pay. portal.'));
                    $this->orderRepository->save($order);
                }
                $this->deactivateCart($order, $payOrderId);
            } elseif ($bPending) {
                $successUrl = ($this->config->getPendingPage() || $this->config->sendEcommerceAnalytics()) ? Config::PENDING_PAY : Config::FINISH_STANDARD;
                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
                $this->deactivateCart($order, $payOrderId);
            } else {
                $cancelMessage = $bDenied ? __('Payment denied') : __('Payment canceled');
                $this->messageManager->addNoticeMessage($cancelMessage);
                $resultRedirect->setPath($payment->getMethod() == 'paynl_payment_paylink' ? Config::CANCEL_PAY : $this->config->getCancelURL());
            }
        } catch (\Exception $e) {
            payHelper::logCritical($e->getCode() . ': ' . $e->getMessage(), $params, $order->getStore());

            if ($e->getCode() == 101) {
                $this->messageManager->addNoticeMessage(__('Invalid return, no transactionId specified'));
            } else {
                $this->messageManager->addNoticeMessage(__('Unfortunately something went wrong'));
            }
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
     * @return void
     */
    private function deactivateCart(Order $order, string $payOrderId)
    {
        # Make the cart inactive
        $session = $this->checkoutSession;

        $this->checkSession($order, $payOrderId, $session);

        $quote = $session->getQuote();
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
    }
}
