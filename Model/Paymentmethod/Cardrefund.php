<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Magento\Sales\Model\Order;
use Paynl\Payment\Helper\PayHelper;
use Paynl\Payment\Model\PayPaymentCreate;

class Cardrefund extends PaymentMethod
{
    protected $_code = 'paynl_payment_cardrefund';

    /**
     * Paylink payment block paths
     *
     * @var string
     */
    protected $_formBlockType = \Paynl\Payment\Block\Form\Instore::class;

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2351;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @return object|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($paymentAction == 'order') {
            /** @var Order $order */
            $order = $this->getInfoInstance()->getOrder();

            $additionalData = $order->getPayment()->getAdditionalInformation();
            $terminalId = null;
            if (isset($additionalData['payment_option'])) {
                $terminalId = $additionalData['payment_option'];
            }
            if (empty($terminalId)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please select a pin-terminal'));
            }

            parent::initialize($paymentAction, $stateObject);
        }
    }

    /**
     * @param Order $order
     * @param boolean $fromAdmin
     * @return string|void
     * @throws \Exception
     */
    public function startTransaction(Order $order, $fromAdmin = false)
    {
        $store = $order->getStore();
        $url = $store->getBaseUrl() . 'checkout/cart/';

        $additionalData = $order->getPayment()->getAdditionalInformation();
        $terminalId = null;
        if (isset($additionalData['payment_option'])) {
            $terminalId = $additionalData['payment_option'];
        }
        unset($additionalData['payment_option']);

        try {
            if (empty($terminalId)) {
                if (!empty($this->getDefaultPaymentOption())) {
                    $order->getPayment()->setAdditionalInformation('payment_option', $this->getDefaultPaymentOption());
                    $order->save();
                } else {
                    throw new \Exception(__('Please select a pin-terminal'), 201);
                }
            }

            $transation = (new PayPaymentCreate($order, $this));
            $transation->setAmount($additionalData['refund_amount']);
            $transaction = $transation->create();

            $order->getPayment()->setAdditionalInformation($additionalData);
            $order->save();
        } catch (\Exception $e) {
            $this->payHelper->logCritical($e->getMessage(), [], $store);

            if ($e->getCode() == 201) {
                if ($fromAdmin) {
                    throw new \Exception(__($e->getMessage()));
                } else {
                    $this->messageManager->addNoticeMessage($e->getMessage());
                }
            } else {
                if ($fromAdmin) {
                    throw new \Exception(__('Pin transaction could not be started'));
                } else {
                    $this->messageManager->addNoticeMessage(__('Pin transaction could not be started'));
                }
            }
        }

        return $transaction->getRedirectUrl();
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('payment_option', $data['payment_option']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['payment_option'])) {
                $paymentOption = $additional_data['payment_option'];
                $this->getInfoInstance()->setAdditionalInformation('payment_option', $paymentOption);
            }
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultPaymentOption()
    {
        return $this->_scopeConfig->getValue('payment/paynl_payment_instore/default_terminal', 'store');
    }

}
