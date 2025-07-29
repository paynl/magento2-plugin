<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Paynl\Error\Error;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;

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
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        PaymentHelper $paymentHelper,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        PayHelper $payHelper
    ) {
        $this->config          = $config; // PAY. config helper
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper   = $paymentHelper;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->payHelper = $payHelper;

        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();

            if (empty($order)) {
                throw new Error('No order found in session, please try again');
            }

            $payment = $order->getPayment();

            if (empty($payment)) {
                throw new Error('No payment found');
            }

            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());
            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                $this->payHelper->logInfo('Start new payment for order ' . $order->getId() . '. PayProfileId: ' . $methodInstance->getPaymentOptionId(), array(), $order->getStore());

                if ($this->config->restoreQuote()) {
                    $orderId = $this->_getCheckoutSession()->getLastRealOrderId();
                    $this->_getCheckoutSession()->restoreQuote();
                    $this->_getCheckoutSession()->setLastRealOrderId($orderId);
                }

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
