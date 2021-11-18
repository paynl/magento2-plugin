<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Overboeking extends PaymentMethod
{
    protected $_code = 'paynl_payment_overboeking';

    protected function getDefaultPaymentOptionId()
    {
        return 136;
    }
}
