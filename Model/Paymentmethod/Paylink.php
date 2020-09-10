<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Sales\Model\Order;

/**
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Paylink extends PaymentMethod
{
    protected $_code = 'paynl_payment_paylink';

    protected function getDefaultPaymentOptionId()
    {
        return 961;
    }

    /**
     * Paylink payment block paths
     *
     * @var string
     */
    protected $_formBlockType = \Paynl\Payment\Block\Form\Paylink::class;

    // this is an admin only method
    protected $_canUseCheckout = false;


    public function initialize($paymentAction, $stateObject)
    {
        if ($paymentAction == 'order') {
            /** @var Order $order */
            $order = $this->getInfoInstance()->getOrder();
            $this->orderRepository->save($order);

            $transaction = $this->doStartTransaction($order);

            $status = $this->getConfigData('order_status');
            $url = $transaction->getRedirectUrl();
            $order->addStatusHistoryComment('Betaallink: ' . $url, $status);
            
            $ObjectManager = \Magento\Framework\App\ObjectManager::GetInstance();
  
            $orderCommentSender = $ObjectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
            $orderCommentSender->send($order, true, 'Betaallink: ' . $url);

            parent::initialize($paymentAction, $stateObject);
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->getInfoInstance()->setAdditionalInformation('valid_days', $data->getData('additional_data')['valid_days']);

        return parent::assignData($data);
    }
}