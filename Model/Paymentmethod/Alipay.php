<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Alipay extends PaymentMethod
{
    protected $_code = 'paynl_payment_alipay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2080;
    }
}
