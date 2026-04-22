<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Nationalegolfbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_nationalegolfbon';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5229;
    }
}
