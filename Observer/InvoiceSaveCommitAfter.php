<?php

namespace Paynl\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\Config;
use Psr\Log\LoggerInterface;

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

    /**
     *
     * @var \Paynl\Payment\Helper\PayHelper;
     */
    protected $payHelper;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param Config $config
     * @param \Magento\Framework\App\RequestInterface $request
     * @param PayHelper $payHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Config $config,
        \Magento\Framework\App\RequestInterface $request,
        PayHelper $payHelper
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->_request = $request;
        $this->payHelper = $payHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if ($methodInstance instanceof \Paynl\Payment\Model\Paymentmethod\PaymentMethod) {
            $postdata = $this->_request->getPost();
            if (!empty($postdata['invoice']['capture_case']) && $postdata['invoice']['capture_case'] == "online" && $order->getState() == Order::STATE_PROCESSING) {
                $this->config->setStore($order->getStore());
                $paymentMethod = $order->getPayment()->getMethod();
                $customStatus = $this->config->getPaidStatus($paymentMethod);
                if (!empty($customStatus)) {
                    $this->payHelper->logNotice('PAY.: Updating order status from ' . $order->getStatus() . ' to ' . $customStatus, [], $order->getStore());
                    $order->setStatus($customStatus)->save();
                }
            }
        }
    }
}
