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
        QuoteRepository $quoteRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentHelper = $paymentHelper;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }


    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $orderId = isset($params['order_id']) ? $params['order_id'] : null;
        $returnUrl = isset($params['return_url']) ? urldecode($params['return_url']) : null;
        $redirectUrl = '';

        try {
            $order = $this->orderRepository->get($orderId);

            $quote = $this->quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(true);
            $this->quoteRepository->save($quote);

            $payment = $order->getPayment();
            $payment->setAdditionalInformation('returnUrl', $returnUrl);

            $methodInstance = $this->paymentHelper->getMethodInstance($payment->getMethod());
            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                $redirectUrl = $methodInstance->startTransaction($order, true);
            }
        } catch (\Exception $e) {
            PayHelper::setCookie('pinError', $e->getMessage());
        }

        if (!empty($redirectUrl)) {
            header("Location: " . $redirectUrl);
        } else {
            header("Location: " . $returnUrl);
        }
        exit;
    }
}
