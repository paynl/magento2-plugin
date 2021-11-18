<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Payconiq extends PaymentMethod
{
    protected $_code = 'paynl_payment_payconiq';

    protected function getDefaultPaymentOptionId()
    {
        return 2379;
    }
}
