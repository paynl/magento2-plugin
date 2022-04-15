<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Paynl\Payment\Model\Config;
use Magento\Sales\Model\Order;
use \Paynl\Payment\Helper\PayHelper;

class InvoiceSaveCommitAfter implements ObserverInterface
{

    /**
     *
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var \Paynl\Payment\Model\Config
     */
    private $config;

    /**
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $postdata = $this->_request->getPost();

        if (!empty($postdata['invoice']['capture_case']) && $postdata['invoice']['capture_case'] == "online" && $order->getState() == Order::STATE_PROCESSING) {
            $this->config->setStore($order->getStore());
            $paymentMethod = $order->getPayment()->getMethod();
            $customStatus = $this->config->getPaidStatus($paymentMethod);
            if (!empty($customStatus)) {
                payHelper::logNotice('PAY.: Updating order status from ' . $order->getStatus() . ' to ' . $customStatus, [], $order->getStore());
                $order->setStatus($customStatus)->save();
            }
        }
    }
}
