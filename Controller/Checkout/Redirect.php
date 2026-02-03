<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Sales\Model\Order as OrderModel;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Exception;

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
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    private $payHelper;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var OrderModel
     */
    private $orderModel;

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

    /**
     * @param $mqId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getOrder($mqId)
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($mqId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {

            # Numeric id's only for logged-in customers
            if (!ctype_digit((string)$mqId)) {
                throw new \Exception('Invalid quote reference: ' . $mqId);
            }

            $sessionQuote = $this->checkoutSession->getQuote();

            # Check if a user is logged
            if (!$sessionQuote->getCustomerId()) {
                throw new \Exception('Invalid reference: ' . $mqId);
            }

            $quoteId = (int)$mqId;
            $quote = $this->quoteRepository->get($quoteId);

            # Check if the user is owner of the given quote
            if ((int)$quote->getCustomerId() !== (int)$sessionQuote->getCustomerId()) {
                throw new \Exception('Invalid quote reference: ' . $mqId);
            }
        }

        $quote ??= $this->quoteRepository->get($quoteId);
        $incrementId = $quote->getReservedOrderId();
        $orderId = $this->orderModel->loadByIncrementId($incrementId)->getId();
        $order = $this->orderRepository->get($orderId);
        if (empty($order)) {
            throw new \Exception('Could not find order by mqId');
        }
        return $order;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $rMode = $this->config->getPaymentRedirectMode();

        try {
            if ($rMode == 'get') {
                $mqId = $this->getRequest()->getParam('mqid');
                $this->payHelper->logDebug(__METHOD__. ': Starting payment with mqId: ' . $mqId);
                try {
                    $order = $this->getOrder($mqId);
                } catch (Exception $e) {
                    throw new Exception('Could not retrieve order by mqId. Exception: ' . $e->getMessage());
                }
                $this->payHelper->logDebug(__METHOD__.': OrderId from quote: ' . $order->getId() ?? null, array(), $order->getStore() ?? null);
            } else {
                $order = $this->checkoutSession->getLastRealOrder();
            }

            if (empty($order)) {
                throw new Exception('No order found in session, please try again');
            }

            $payment = $order->getPayment();
            if (empty($payment)) {
                throw new Exception('No payment found');
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
                throw new Exception('PAY.: Method is not a paynl payment method');
            }
        } catch (Exception $e) {
            $this->_getCheckoutSession()->restoreQuote(); // phpcs:ignore
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->payHelper->logCritical($e->getMessage());
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
