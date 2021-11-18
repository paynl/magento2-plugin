<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Klarna extends PaymentMethod
{
    protected $_code = 'paynl_payment_klarna';

    protected function getDefaultPaymentOptionId()
    {
        return 1717;
    }
}
