<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Przelewy24 extends PaymentMethod
{
    protected $_code = 'paynl_payment_przelewy24';

    protected function getDefaultPaymentOptionId()
    {
        return 2151;
    }
}
