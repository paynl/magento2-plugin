<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Doenkado extends PaymentMethod
{
    protected $_code = 'paynl_payment_doenkado';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3132;
    }
}
