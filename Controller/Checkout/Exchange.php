<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Controller\Checkout;

/**
 * Description of Index
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Exchange extends \Magento\Framework\App\Action\Action
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
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     *
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Framework\Controller\Result\Raw
     */
    protected $_result;

    /**
     * Exchange constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Paynl\Payment\Model\Config $config
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Controller\Result $result
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Paynl\Payment\Model\Config $config,
    \Magento\Sales\Model\OrderFactory $orderFactory,
    \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
    \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
    \Psr\Log\LoggerInterface $logger,
    \Magento\Framework\Controller\Result\Raw $result
    )
    {
        $this->_result       = $result;
        $this->_config       = $config;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender  = $orderSender;
        $this->_invoiceSender = $invoiceSender;
        $this->_logger       = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $skipFraudDetection = false;
        \Paynl\Config::setApiToken($this->_config->getApiToken());

        $params = $this->getRequest()->getParams();
        if(!isset($params['order_id'])){
            $this->_logger->critical('Exchange: order_id is not set in the request', $params);
            return $this->_result->setContents('FALSE| order_id is not set in the request');
        }

        try{
            $transaction = \Paynl\Transaction::get($params['order_id']);
        } catch(\Exception $e){
            $this->_logger->critical($e, $params);
            return $this->_result->setContents('FALSE| Error fetching transaction. '. $e->getMessage());
        }

        if($transaction->isPending()){
            return $this->_result->setContents("TRUE| Ignoring pending");
        }

        $orderId     = $transaction->getDescription();
        $order       = $this->_orderFactory->create()->loadByIncrementId($orderId);

        if(empty($order)){
            $this->_logger->critical('Cannot load order: '.$orderId);
            return $this->_result->setContents('FALSE| Cannot load order');
        }
        if($order->getTotalDue() <= 0){
            $this->_logger->debug('Total due <= 0, so iam not touching the status of the order: '.$orderId);
            return $this->_result->setContents('TRUE| Total due <= 0, so iam not touching the status of the order');
        }

        if ($transaction->isPaid()) {
            $payment = $order->getPayment();
            $payment->setTransactionId(
                $transaction->getId()
            );
            $payment->setCurrencyCode(
                $transaction->getPaidCurrency()
            );
            $payment->setIsTransactionClosed(
                0
            );
            $payment->registerCaptureNotification(
                $transaction->getPaidCurrencyAmount(), $skipFraudDetection
            );
            $order->save();

            // notify customer
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && !$order->getEmailSent()) {
                $this->_orderSender->send($order);
                $order->addStatusHistoryComment(
                    __('New order email sent')
                )->setIsCustomerNotified(
                    true
                )->save();
            }
            if($invoice && !$invoice->getEmailSent()){
                $this->_invoiceSender->send($invoice);

                $order->addStatusHistoryComment(
                    __('You notified customer about invoice #%1.',
                        $invoice->getIncrementId())
                )->setIsCustomerNotified(
                    true
                )->save();

            }
            return $this->_result->setContents("TRUE| PAID");

        } elseif($transaction->isCanceled()){
            $order->cancel()->save();
            return $this->_result->setContents("TRUE| CANCELED");
        }

    }
}