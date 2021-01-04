<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Controller\PayAction;
use Paynl\Payment\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Description of Redirect
 *
 * @author Andy Pieters <andy@pay.nl>
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
     * @var LoggerInterface
     */
	private $logger;

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
     * @param LoggerInterface $logger
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        LoggerInterface $logger,
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
        if(!isset($params['orderId']) && !isset($params['orderid'])){
            $this->messageManager->addNoticeMessage(__('Invalid return, no transactionId specified'));
            $this->logger->critical('Invalid return, no transactionId specified', $params);
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
        try{
            if (empty($params['orderId']))
                $params['orderId'] = $params['orderid'];
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

        if ($transaction->isPaid() || $transaction->isAuthorized() || ($transaction->isPending() && $pinStatus == null)) {
            $successUrl = $this->config->getSuccessPage($payment->getMethod());
            $resultRedirect->setPath($successUrl, ['_query' => ['utm_nooverride' => '1']]);
            
            if($payment->toArray()['method'] == 'paynl_payment_paylink'){
                $resultRedirect->setPath('paynl/checkout/paylink/');
            }

            // make the cart inactive
	        $session = $this->checkoutSession;

	        $quote = $session->getQuote();
	        $quote->setIsActive(false);
	        $this->quoteRepository->save($quote);

            return $resultRedirect;
        }

        $this->messageManager->addNoticeMessage(__('Payment canceled'));
        $cancelURL = $this->config->getCancelURL();
        $resultRedirect->setPath($cancelURL);


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
