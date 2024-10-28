<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Sales\Model\Order as OrderModel;
use Paynl\Error\Error;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
//use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Redirects the user after payment
 */
class Redirect extends PayAction
{
    /**
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var OrderModel
     */
    private $orderModel;

    /**
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param PayHelper $payHelper
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param OrderModel $orderModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        PaymentHelper $paymentHelper,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        PayHelper $payHelper,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        OrderModel $orderModel
    ) {
        $this->config          = $config; // PAY. config helper
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper   = $paymentHelper;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->payHelper = $payHelper;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->orderModel = $orderModel;

        parent::__construct($context);
    }



    private function canAccessOrder($order)
    {
        # Check for guest users by session's last placed order
        if (!$this->checkoutSession->getCustomerId()) {
            $this->payHelper->logDebug('mqid: ' . $this->checkoutSession->getLastOrderId() . ' vs ' . $order->getEntityId());
            return $this->checkoutSession->getLastOrderId() === $order->getEntityId();
        }
        return false;
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $mqid = $this->getRequest()->getParam('mqid');
            $this->payHelper->logDebug('mqid: ' . $mqid);

            if (!empty($mqid)) {
                try {
                    $quoteId = $this->maskedQuoteIdToQuoteId->execute($mqid);
                    $quote = $this->quoteRepository->get($quoteId);
                    $incrementId = $quote->getReservedOrderId();
                    $orderId = $this->orderModel->loadByIncrementId($incrementId)->getId();
                    $order = $this->orderRepository->get($orderId);
                    if (!$this->canAccessOrder($order)) {
                        $this->payHelper->logDebug('Unauthorized access to order.');
                        $this->messageManager->addErrorMessage(__('Unauthorized access to order.'));
                        return $this->_redirect('checkout/cart');
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->payHelper->logDebug('Order not found: ' . $e->getMessage());
                    $this->messageManager->addErrorMessage(__('Order not found.'));
                    return $this->_redirect('checkout/cart');
                }

                $this->payHelper->logDebug('Redirect: OrderId from quote: ' . $order->getId() ?? null, array(), $order->getStore() ?? null);

                # Temp, for debug purposes:
                $orderSession = $this->checkoutSession->getLastRealOrder();
                $qid = $this->checkoutSession->getQuoteId();
                $this->payHelper->logDebug('Redirect: OrderId from session: ' . ($orderSession->getId() ?? null) . '. qid:' . $qid, array(), $order->getStore() ?? null);

                if (empty($order)) {
                    $order = $orderSession;
                }
            } else {
                $order = $this->checkoutSession->getLastRealOrder();
            }

            if (empty($order)) {
                throw new Error('No order found in session, please try again');
            }

            $payment = $order->getPayment();

            if (empty($payment)) {
                throw new Error('No payment found');
            }

            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());
            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                $this->payHelper->logNotice('Start new payment for order ' . $order->getId() . '. PayProfileId: ' . $methodInstance->getPaymentOptionId(), array(), $order->getStore());
                $redirectUrl = $methodInstance->startTransaction($order);
                $this->getResponse()->setNoCacheHeaders();
                $this->getResponse()->setRedirect($redirectUrl);
            } else {
                throw new Error('PAY.: Method is not a paynl payment method');
            }
        } catch (\Exception $e) {
            $this->_getCheckoutSession()->restoreQuote(); // phpcs:ignore
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->payHelper->logCritical($e->getMessage(), array(), $order->getStore());
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession() // phpcs:ignore
    {
        return $this->checkoutSession;
    }
}
