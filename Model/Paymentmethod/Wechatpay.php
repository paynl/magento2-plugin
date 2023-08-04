<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Wechatpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_wechatpay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1978;
    }
}
