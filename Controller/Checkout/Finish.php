<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
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
    protected $_config;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        OrderRepository $orderRepository
    )
    {
        $this->_config = $config;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->orderRepository = $orderRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        \Paynl\Config::setApiToken($this->_config->getApiToken());
        $params = $this->getRequest()->getParams();
        if(!isset($params['orderId'])){
            $this->messageManager->addNoticeMessage(__('Invalid return, no transactionId specified'));
            $this->_logger->critical('Invalid return, no transactionId specified', $params);
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
        try{
            $transaction = \Paynl\Transaction::get($params['orderId']);
        } catch(\Exception $e){
            $this->_logger->critical($e, $params);
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
            $this->_getCheckoutSession()->start();
            $resultRedirect->setPath('checkout/onepage/success');
        } else {
            //canceled, re-activate quote
            try {
                $this->_getCheckoutSession()->restoreQuote();
                $this->messageManager->addNoticeMessage(__('Payment canceled'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_logger->error($e);
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->_logger->error($e);
                $this->messageManager->addExceptionMessage($e, __('Unable to cancel order'));
            }
            $resultRedirect->setPath('checkout/cart');
        }

        if(in_array($pinStatus,[
            'cancelled',
            'expired',
            'error'
        ])){
            // er komt hier geen cancel voor binnen, dus doen we het hier
            $order->cancel();
            $this->orderRepository->save($order);
        }
        return $resultRedirect;
    }

    /**
     * Return checkout session object
     *
     * @return Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}