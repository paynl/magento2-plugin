<?php

namespace Paynl\Payment\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Model\OrderRepository;
use Paynl\Payment\Helper\PayHelper;

class View extends Template
{
    public const PAY_TRANSACTION_URL = 'https://my.pay.nl/transactions/info/';
    private $orderRepository;


    /**
     * @param Context $context
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return string
     */
    public function getPayUrl()
    {
        return self::PAY_TRANSACTION_URL . $this->getId();
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        $params = $this->getRequest()->getParams();
        $orderId = isset($params['order_id']) ? $params['order_id'] : null;

        try {
            $order = $this->orderRepository->get($orderId);
            $getPayment = $order->getPayment();
        } catch (\Exception $e) {
            payHelper::logCritical($e, $params);
        }

        return $getPayment;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $additionalInformation = $this->getOrder()->getAdditionalInformation();
        return $additionalInformation['transactionId'] ?? '';
    }
}
