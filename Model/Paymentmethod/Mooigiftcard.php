<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Mooigiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_mooigiftcard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3183;
    }
}
