<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Bioscoopbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_bioscoopbon';

    protected function getDefaultPaymentOptionId()
    {
        return 2133;
    }
}