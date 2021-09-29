<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Creditclick extends PaymentMethod
{
    protected $_code = 'paynl_payment_creditclick';

    protected function getDefaultPaymentOptionId()
    {
        return 2107;
    }
}
