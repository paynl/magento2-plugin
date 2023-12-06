<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Horsesandgifts extends PaymentMethod
{
    protected $_code = 'paynl_payment_horsesandgifts';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3222;
    }
}
