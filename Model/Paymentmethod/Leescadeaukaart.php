<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Leescadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_leescadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4758;
    }
}
