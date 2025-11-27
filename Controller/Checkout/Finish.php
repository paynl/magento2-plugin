<?php

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use PayNL\Sdk\Exception\PayException;
use PayNL\Sdk\Model\Method;
use PayNL\Sdk\Model\Request\OrderStatusRequest;
use PayNL\Sdk\Config\Config as PaySDKConfig;

class Finish extends PayAction
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var
     */
    protected $productRepository;

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
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        PayHelper $payHelper,
        ManagerInterface $eventManager,
        QuoteFactory $quoteFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->payHelper = $payHelper;
        $this->eventManager = $eventManager;
        $this->quoteFactory = $quoteFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     * Check if session is active.
     * @param Order $order
     * @param string $orderId
     * @param Session $session
     * @param boolean $emptyOrder
     * @return void
     */
    private function checkSession(Order $order, string $orderId, Session $session, $emptyOrder = null)
    {
        if ($session->getLastOrderId() != $order->getId()) {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            $transactionId = (isset($additionalInformation['transactionId'])) ? $additionalInformation['transactionId'] : null;

            if ($orderId == $transactionId || !empty($emptyOrder)) {
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
    private function checkEmpty($field, string $name, int $errorCode, ?string $desc = null)
    {
        if (empty($field)) {
            $desc = empty($desc) ? $name . ' is empty' : $desc;
            throw new \Exception('Finish: ' . $desc, $errorCode);
        }
    }

    /**
     * @param boolean $bSuccess
     * @param boolean $bPending
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function getFastCheckoutPath($bSuccess, $bPending)
    {
        $path = $bSuccess ? Config::FINISH_PAY_FC : ($bPending ? Config::PENDING_PAY : 'checkout/cart');
        $session = $this->checkoutSession;
        $quote = $session->getQuote();

        if ($bSuccess || $bPending) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
        } else {
            $quote->setIsActive(true);
            $this->quoteRepository->save($quote);
            $session->replaceQuote($quote);
        }

        return $path;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function unloadCart()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote && $quote->getId()) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
        }
    }

    /**
     * @return resultRedirectFactory|void
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $this->getRequest()->getParams();
        $payOrderId = $params['id'] ?? null;
        $statusAction = $params['statusAction'] ?? null;

        $orderStatusId = empty($params['statusCode']) ? null : (int)$params['statusCode'];

        $entityid = $params['entityid'] ?? null;
        $magOrderId = $params['reference'] ?? null;
        $orderIds = empty($params['order_ids']) ? null : $params['order_ids'];
        $pickupMode = !empty($params['pickup']);
        $invoice = !empty($params['invoice']);

        $bSuccess = $orderStatusId === Config::ORDERSTATUS_PAID || $orderStatusId === Config::ORDERSTATUS_AUTH;
        $bPending = in_array($orderStatusId, Config::ORDERSTATUS_PENDING);
        $bDenied = in_array($orderStatusId, Config::ORDERSTATUS_DENIED);
        $bCanceled = $orderStatusId === Config::ORDERSTATUS_CANCELED;
        $bVerify = $orderStatusId === Config::ORDERSTATUS_VERIFY;
        $bConfirm = $orderStatusId === Config::ORDERSTATUS_CONFIRM;
        $isPinTransaction = false;
        $multiShipFinish = is_array($orderIds);

        try {
            if ($entityid == 'fc') {
                $resultRedirect->setPath($this->getFastCheckoutPath($bSuccess, $bPending), ['_query' => ['utm_nooverride' => '1']]);
                return $resultRedirect;
            }

            $this->checkEmpty($magOrderId, 'magOrderId', 1012);
            $order = $this->orderRepository->get($magOrderId);
            $this->checkEmpty($order, 'order', 1013);

            if ($pickupMode || $invoice) {
                $this->unloadCart();
                $resultRedirect->setPath($pickupMode ? Config::FINISH_PICKUP : Config::FINISH_INVOICE, ['_query' => ['utm_nooverride' => '1']]);
                return $resultRedirect;
            }

            $this->checkEmpty($payOrderId, 'payOrderid', 101);
            $this->config->setStore($order->getStore());

            $payment = $order->getPayment();
            $information = $payment->getAdditionalInformation();

            $this->checkEmpty(($information['transactionId'] ?? null) == $payOrderId, '', 1014, 'transaction mismatch');

            if (($information['pintrans'] ?? false) === true) {
                $bPending = false;
                $isPinTransaction = true;
                $pinStatus = $this->handlePin($order, $statusAction);
                $bSuccess = $pinStatus === true;
            }

            if (empty($bSuccess) && !$isPinTransaction) {
                $payOrder = (new OrderStatusRequest($payOrderId))->setConfig($this->config->getPayConfig())->start();
                $orderNumber = $payOrder->getReference();
                $this->checkEmpty($order->getId() == $orderNumber, '', 104, 'order mismatch');
                $bSuccess = ($payOrder->isPaid() || $payOrder->isAuthorized());
            }

            if ($bSuccess || $bVerify || $bConfirm) {
                $successUrl = $this->config->getSuccessPage($payment->getMethod());
                if (empty($successUrl)) {
                    $successUrl = (in_array($payment->getMethod(), ['paynl_payment_paylink', 'paynl_payment_invoice']) || $this->config->sendEcommerceAnalytics()) ? Config::FINISH_PAY : Config::FINISH_STANDARD;
                }
                if ($bConfirm) {
                    $successUrl = Config::CONFIRM_PAY;
                }
                $this->payHelper->logDebug('Finish succes', [$successUrl, $payOrderId, $bSuccess, $bVerify]);
                $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
                if ($isPinTransaction && $pinStatus !== true) {
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

                $this->config->maintainQuoteOnCancel() ? $this->reactivateCart($order) : $this->initiateNewQuote($order);

                if ($multiShipFinish) {
                    $session = $this->checkoutSession;
                    $sessionId = $session->getLastQuoteId();
                    $quote = $this->quoteFactory->create()->loadByIdWithoutStore($sessionId);
                    if (!empty($quote->getId())) {
                        $quote->setIsActive(true)->setReservedOrderId(null)->save();
                        $session->replaceQuote($quote);
                    }
                }
                $cancelUrl = in_array($payment->getMethod(), ['paynl_payment_paylink', 'paynl_payment_invoice']) ? Config::CANCEL_PAY : $this->config->getCancelURL();
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

            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }

    /**
     * @param Order $order
     * @param string $status
     * @return bool
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function handlePin(Order $order, string $status)
    {
        $result = true;
        if (in_array($status, ['PENDING', 'CANCELLED', 'ERROR']))
            if ($status != 'PAID') {
                $result = false;
                # Instore does not send a canceled exchange message, so cancel it here
                $order->cancel();
                $this->orderRepository->save($order);
                return false;
            }
        return $result;
    }

    /**
     * @param Order $cancelledOrder
     * @return void
     */
    private function initiateNewQuote(Order $cancelledOrder)
    {
        # Retrieve the quote
        $quote = $this->quoteFactory->create()->load($cancelledOrder->getQuoteId());
        $orderItems = $quote->getAllItems();

        $newQuote = $this->quoteFactory->create();
        $newQuote->setStoreId($cancelledOrder->getStoreId());

        $this->payHelper->logDebug('initiateNewQuote', [$newQuote->getId()]);

        # Update the new quote with customerdata
        if ($cancelledOrder->getCustomerId()) {
            $newQuote->setCustomerId($cancelledOrder->getCustomerId());
        } else {
            # Guest-customers
            $newQuote->setCustomerIsGuest(true);
            $newQuote->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);            
        }

        $newQuote->setCustomerEmail($cancelledOrder->getCustomerEmail());
        $newQuote->setCustomerFirstname($cancelledOrder->getCustomerFirstname());
        $newQuote->setCustomerLastname($cancelledOrder->getCustomerLastname());
        
        $newQuote->setCustomerTelephone($cancelledOrder->getCustomerTelephone());

        $newQuote->setCustomerPrefix($cancelledOrder->getCustomerPrefix());
        $newQuote->setCustomerSuffix($cancelledOrder->getCustomerSuffix());
        $newQuote->setCustomerDob($cancelledOrder->getCustomerDob());
        $newQuote->setCustomerTaxvat($cancelledOrder->getCustomerTaxvat());
        $newQuote->setCustomerGender($cancelledOrder->getCustomerGender());

        $billingAddress = $cancelledOrder->getBillingAddress();
        if ($billingAddress) {
            $newBillingAddress = $newQuote->getBillingAddress();
            $newBillingAddress->addData($billingAddress->getData());
        }

        $shippingAddress = $cancelledOrder->getShippingAddress();
        if ($shippingAddress) {
            $newShippingAddress = $newQuote->getShippingAddress();
            $newShippingAddress->addData($shippingAddress->getData());
        }

        # Add products to the new quote
        if (is_array($orderItems)) {
            foreach ($orderItems as $item) {
                try {
                    $product = $this->productRepository->getById($item->getProductId());
                    $newQuote->addProduct($product, (int)$item->getQty());
                } catch (\Exception $e) {
                    $this->payHelper->logDebug('PAY.: Error adding product to new quote: ' . $e->getMessage(), [$item->getProductId()]);
                }
            }
        }

        $newQuote->setIsActive(true);
        $this->quoteRepository->save( $newQuote);
        $this->checkoutSession->replaceQuote($newQuote);
    }

    /**
     * @param Order $order
     * @param string $payOrderId
     * @param boolean $emptyOrder
     * @return void
     */
    private function deactivateCart(Order $order, string $payOrderId, $emptyOrder = null)
    {
        $session = $this->checkoutSession;
        $this->checkSession($order, $payOrderId, $session, $emptyOrder);
        $this->unloadCart();
    }

    /**
     * @param Order $order
     * @return void
     */
    private function reactivateCart(Order $order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(true)->setReservedOrderId(null);
        $this->checkoutSession->replaceQuote($quote);
        $this->quoteRepository->save($quote);
    }
}
