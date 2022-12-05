<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Sodexo extends PaymentMethod
{
    protected $_code = 'paynl_payment_sodexo';

    protected function getDefaultPaymentOptionId()
    {
        return 3030;
    }
}
