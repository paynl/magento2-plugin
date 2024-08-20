<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Alma extends PaymentMethod
{
    protected $_code = 'paynl_payment_alma';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3552;
    }
}
