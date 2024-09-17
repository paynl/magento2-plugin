<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Kunstencultuurkaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_kunstencultuurkaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3258;
    }
}
