<?php

namespace Paynl\Payment\Controller\Order;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Quote\Model\QuoteRepository;
use Magento\Payment\Helper\Data as PaymentHelper;

use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Controller\PayAction;
use \Paynl\Payment\Helper\PayHelper;

class Instore extends PayAction implements CsrfAwareActionInterface
{
    private $orderRepository;
    private $quoteRepository;
    private $paymentHelper;

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * Instore constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param Magento\Sales\Model\OrderRepository $orderRepository
     * @param Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        OrderRepository $orderRepository,
        PaymentHelper $paymentHelper,
        QuoteRepository $quoteRepository,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentHelper = $paymentHelper;
        $this->quoteRepository = $quoteRepository;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;

        parent::__construct($context);
    }


    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $orderId = isset($params['order_id']) ? $params['order_id'] : null;
        $returnUrl = isset($params['return_url']) ? urldecode($params['return_url']) : null;

        try {
            $order = $this->orderRepository->get($orderId);

            $redirectUrl = '';

            $quote = $this->quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(true);
            $this->quoteRepository->save($quote);

            $payment = $order->getPayment();
            $payment->setAdditionalInformation('returnUrl', $returnUrl);

            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());
            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                $redirectUrl = $methodInstance->startTransaction($order, true);
            }

            if (!empty($redirectUrl)) {
                header("Location: " . $redirectUrl);
                exit;
            } else {
                header("Location: " . $returnUrl);
                exit;
            }
        } catch (\Exception $e) {
            $this->setCookie('pinError', $e->getMessage());
            header("Location: " . $returnUrl);
            exit;
        }
    }

    public function setCookie($cookieName, $value)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(300)
            ->setSecure(false)
            ->setPath('/')
            ->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(
            $cookieName,
            $value,
            $metadata
        );
    }
}
