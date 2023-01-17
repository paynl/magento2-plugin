<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Bloemencadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_bloemencadeaukaart';

    protected function getDefaultPaymentOptionId()
    {
        return 2607;
    }
}