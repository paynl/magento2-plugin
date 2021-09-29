<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Podiumcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_podiumcadeaukaart';

    protected function getDefaultPaymentOptionId()
    {
        return 816;
    }
}
