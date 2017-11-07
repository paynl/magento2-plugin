<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Description of Redirect
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Finish extends \Magento\Framework\App\Action\Action
{
    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    protected $config;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

	/**
	 * @var QuoteRepository
	 */
	protected $quoteRepository;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        OrderRepository $orderRepository,
		QuoteRepository $quoteRepository
    )
    {
        $this->config           = $config;
        $this->checkoutSession  = $checkoutSession;
        $this->logger           = $logger;
        $this->orderRepository  = $orderRepository;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        \Paynl\Config::setApiToken($this->config->getApiToken());
        $params = $this->getRequest()->getParams();
        if(!isset($params['orderId'])){
            $this->messageManager->addNoticeMessage(__('Invalid return, no transactionId specified'));
            $this->logger->critical('Invalid return, no transactionId specified', $params);
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
        try{
            $transaction = \Paynl\Transaction::get($params['orderId']);
        } catch(\Exception $e){
            $this->logger->critical($e, $params);
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status'));
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        /**
         * @var Order $order
         */
        $order = $this->orderRepository->get($transaction->getExtra3());
        $payment = $order->getPayment();
        $information = $payment->getAdditionalInformation();
        $pinStatus = null;
        if(isset($information['terminal_hash'])){
            $hash = $information['terminal_hash'];
            $status = \Paynl\Instore::status([
                'hash' => $hash
            ]);
            $pinStatus = $status->getTransactionState();
        }

        if ($transaction->isPaid() || ($transaction->isPending() && $pinStatus == null)) {
            $resultRedirect->setPath('checkout/onepage/success');

            // make the cart inactive
	        $session = $this->checkoutSession;

	        $quote = $session->getQuote();
	        $quote->setIsActive(false);
	        $this->quoteRepository->save($quote);

            return $resultRedirect;
        }

        $this->messageManager->addNoticeMessage(__('Payment canceled'));
        $resultRedirect->setPath('checkout/cart');


        if(in_array($pinStatus,[
            'cancelled',
            'expired',
            'error'
        ])){
            // Instore does not send a canceled exchange message, so we cancel it here
            $order->cancel();
            $this->orderRepository->save($order);
        }
        return $resultRedirect;
    }

}