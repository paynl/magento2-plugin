<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Tikkie extends PaymentMethod
{
    protected $_code = 'paynl_payment_tikkie';

    protected function getDefaultPaymentOptionId()
    {
        return 2104;
    }
}
