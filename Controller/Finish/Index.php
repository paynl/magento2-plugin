<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller\Finish;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Description of Redirect
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    protected $_config;

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param Session $checkoutSession
     * @param OrderSender $orderSender
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Paynl\Payment\Model\Config $config,
        Session $checkoutSession,
        OrderSender $orderSender,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_config = $config;
        $this->_checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->_orderFactory = $orderFactory;

        parent::__construct($context);
    }


    public function execute()
    {
        \Paynl\Config::setApiToken($this->_config->getApiToken());
        $transaction = \Paynl\Transaction::getForReturn();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_getCheckoutSession()->getLastRealOrder();

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($transaction->isPaid() || $transaction->isPending()) {
            $resultRedirect->setPath('checkout/onepage/success');
        } else {
            //canceled, re-activate quote
            try {
                // if there is an order - cancel it
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->_getCheckoutSession()->getLastRealOrder();
                if ($order && $order->getId() && $order->getQuoteId() == $this->_getCheckoutSession()->getQuoteId()) {
                    $order->cancel()->save();
                    $this->_getCheckoutSession()->restoreQuote();
                    $this->messageManager->addNotice(__('Payment canceled'));
                } else {
                    $this->messageManager->addNotice(__('Payment canceled, but unable to cancel order'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Unable to cancel order'));
            }
            $resultRedirect->setPath('checkout/cart');
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