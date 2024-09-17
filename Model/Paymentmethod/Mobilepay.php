<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Mobilepay extends PaymentMethod
{
    protected $_code = 'paynl_payment_mobilepay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3558;
    }
}
