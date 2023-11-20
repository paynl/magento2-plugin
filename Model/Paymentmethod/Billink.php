<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Billink extends PaymentMethod
{
    protected $_code = 'paynl_payment_billink';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1672;
    }
}
