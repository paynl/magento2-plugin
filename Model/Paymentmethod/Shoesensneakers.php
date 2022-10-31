<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Shoesensneakers extends PaymentMethod
{
    protected $_code = 'paynl_payment_shoesensneakers';

    protected function getDefaultPaymentOptionId()
    {
        return 2937;
    }
}
