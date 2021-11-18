<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Yourgift extends PaymentMethod
{
    protected $_code = 'paynl_payment_yourgift';

    protected function getDefaultPaymentOptionId()
    {
        return 1645;
    }
}
