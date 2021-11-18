<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Capayable extends PaymentMethod
{
    protected $_code = 'paynl_payment_capayable';

    protected function getDefaultPaymentOptionId()
    {
        return 1744;
    }
}
