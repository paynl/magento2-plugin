<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Parfumcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_parfumcadeaukaart';

    protected function getDefaultPaymentOptionId()
    {
        return 2682;
    }
}