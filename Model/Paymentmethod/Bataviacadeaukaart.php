<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Bataviacadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_bataviacadeaukaart';

    protected function getDefaultPaymentOptionId()
    {
        return 2955;
    }
}
