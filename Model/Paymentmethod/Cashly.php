<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Cashly extends PaymentMethod
{
    protected $_code = 'paynl_payment_cashly';

    protected function getDefaultPaymentOptionId()
    {
        return 1981;
    }
}
