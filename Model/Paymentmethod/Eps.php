<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Eps extends PaymentMethod
{
    protected $_code = 'paynl_payment_eps';

    protected function getDefaultPaymentOptionId()
    {
        return 2062;
    }
}
