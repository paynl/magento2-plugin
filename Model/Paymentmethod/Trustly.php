<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Trustly extends PaymentMethod
{
    protected $_code = 'paynl_payment_trustly';

    protected function getDefaultPaymentOptionId()
    {
        return 2718;
    }
}
