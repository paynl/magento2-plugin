<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Rotterdamcitycard extends PaymentMethod
{
    protected $_code = 'paynl_payment_rotterdamcitycard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3603;
    }
}
