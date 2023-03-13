<?php

namespace Paynl\Payment\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Helper\PayHelper;

class View extends Template
{
    private $orderRepository;

    /**
     * Order constructor.
     *
     * @param Magento\Sales\Model\OrderRepository $orderRepository
     *
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
    }
    public function getPayUrl()
    {
        $baseUrl = "https://my.pay.nl/transactions/info/";
        $transactionId = $this->getId();

        $payUrl = $baseUrl . $transactionId;

        return $payUrl;
    }

    public function getOrder()
    {
        $params = $this->getRequest()->getParams();
        $orderId = isset($params['order_id']) ? $params['order_id'] : null;;

        try {
            $order = $this->orderRepository->get($orderId);
            $getPayment = $order->getPayment();

        } catch (\Exception $e) {
            payHelper::logCritical($e, $params);
        }

        return $getPayment;
    }

    public function getId()
    {
        $id = $this->getOrder()->getAdditionalInformation()['transactionId'];

        return $id;
    }

    public function getPaymentMethod()
    {
        $paymentmethod = $this->getOrder()->getAdditionalInformation()['method_title'];

        return $paymentmethod;
    }
}