<?php

namespace Paynl\Payment\Controller\Adminhtml\Order;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\CsrfAwareActionInterface;
use Paynl\Payment\Helper\PayHelper;

class CardRefund extends \Magento\Backend\App\Action implements CsrfAwareActionInterface
{
    private $orderRepository;
    private $quoteRepository;
    private $paymentHelper;
    protected $resultFactory;

    /**
     * @param RequestInterface $request
     * @return boolean
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
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    protected $payHelper;

    /**
     * Instore constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Magento\Sales\Model\OrderRepository $orderRepository
     * @param PaymentHelper $paymentHelper
     * @param Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param PayHelper $payHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        OrderRepository $orderRepository,
        PaymentHelper $paymentHelper,
        QuoteRepository $quoteRepository,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        PayHelper $payHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentHelper = $paymentHelper;
        $this->quoteRepository = $quoteRepository;
        $this->resultFactory = $resultFactory;
        $this->payHelper = $payHelper;

        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $orderId = isset($params['order_id']) ? $params['order_id'] : null;
        $returnUrl = isset($params['return_url']) ? urldecode($params['return_url']) : null;
        $refundAmount = isset($params['refund_amount']) ? $params['refund_amount'] : 0;
        $terminalId = isset($params['paynl_terminal']) ? $params['paynl_terminal'] : null;
        $redirectUrl = '';

        try {
            $order = $this->orderRepository->get($orderId);

            $quote = $this->quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(true);
            $this->quoteRepository->save($quote);

            $payment = $order->getPayment();

            $payment->setAdditionalInformation('returnUrl', $returnUrl);
            $payment->setAdditionalInformation('refund_amount', $refundAmount);
            $payment->setAdditionalInformation('payment_option', $terminalId);

            $order->save();

            $methodInstance = $this->paymentHelper->getMethodInstance('paynl_payment_cardrefund');
            if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\Paymentmethod) {
                $redirectUrl = $methodInstance->startTransaction($order, true);
            }
        } catch (\Exception $e) {
            $this->payHelper->setCookie('cardRefundError', $e->getMessage());
        }

        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl(!empty($redirectUrl) ? $redirectUrl : $returnUrl);

        return $redirect;
    }
}
