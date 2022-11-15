<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Onlinebankbetaling extends PaymentMethod
{
    protected $_code = 'paynl_payment_onlinebankbetaling';

    protected function getDefaultPaymentOptionId()
    {
        return 2970;
    }
}