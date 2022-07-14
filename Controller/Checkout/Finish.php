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
use \Paynl\Payment\Helper\PayHelper;

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
     */
    public function __construct(Context $context, Config $config, Session $checkoutSession, OrderRepository $orderRepository, QuoteRepository $quoteRepository)
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    private function checkSession(Order $order, $orderId, $session)
    {
        if ($session->getLastOrderId() != $order->getId()) {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            $transactionId = (isset($additionalInformation['transactionId'])) ? $additionalInformation['transactionId'] : null;
            if ($orderId == $transactionId) {
                $this->checkoutSession->setLastQuoteId($order->getQuoteId())->setLastSuccessQuoteId($order->getQuoteId())->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId());
            }
        }
    }

    private function checkEmpty($field, $name, $errorCode, $desc = null)
    {
        if (empty($field)) {
            $desc = empty($desc) ? $name . ' is empty' : $desc;
            throw new \Exception('Finish: ' . $desc, $errorCode);
        }
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getRequest()->getParams();
        $payOrderId = empty($params['orderId']) ? (empty($params['orderid']) ? null : $params['orderid']) : $params['orderId'];
        $orderStatusId = empty($params['orderStatusId']) ? null : (int)$params['orderStatusId'];
        $magOrderId = empty($params['entityid']) ? null : $params['entityid'];
        $bSuccess = $orderStatusId === Config::ORDERSTATUS_PAID;
        $bDenied = $orderStatusId === Config::ORDERSTATUS_DENIED;
        $bCanceled = $orderStatusId === Config::ORDERSTATUS_CANCELED;
        $isPinTransaction = false;

        try {
            $this->checkEmpty($payOrderId, 'payOrderid', 101);
            $this->checkEmpty($magOrderId, 'magOrderId', 1012);

            $order = $this->orderRepository->get($magOrderId);
            $this->checkEmpty($order, 'order', 1013);

            $this->config->setStore($order->getStore());
            \Paynl\Config::setApiToken($this->config->getApiToken());

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
                $transaction = \Paynl\Transaction::get($payOrderId);
                $orderNumber = $transaction->getExtra1();
                $this->checkEmpty($order->getIncrementId() == $orderNumber, '', 104, 'order mismatch');
                $bSuccess = ($transaction->isPaid() || $transaction->isAuthorized() || ($transaction->isPending() && !$bCanceled));
            }

            if ($bSuccess) {
                $successUrl = $this->config->getSuccessPage($payment->getMethod());
                if (empty($successUrl)) {
                    $successUrl = ($payment->getMethod() == 'paynl_payment_paylink' || $this->config->sendEcommerceAnalytics()) ? Config::FINISH_PAY : Config::FINISH_STANDARD;
                }

                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);

                if ($isPinTransaction && $pinStatus->getTransactionState() !== 'approved') {
                    $this->messageManager->addNoticeMessage(__('Order has been made and the payment is pending.'));
                }

                # Make the cart inactive
                $session = $this->checkoutSession;
                if (empty($order)) {
                    $order = $this->getOrder($magOrderId, $payOrderId);
                }
                $this->checkSession($order, $payOrderId, $session);

                $quote = $session->getQuote();
                $quote->setIsActive(false);
                $this->quoteRepository->save($quote);
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
     * @param $hash
     * @param $order
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function handlePin($hash, $order)
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
}
