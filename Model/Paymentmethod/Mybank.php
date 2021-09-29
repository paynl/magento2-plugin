<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Mybank extends PaymentMethod
{
    protected $_code = 'paynl_payment_mybank';

    protected function getDefaultPaymentOptionId()
    {
        return 1855;
    }
}
