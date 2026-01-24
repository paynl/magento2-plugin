<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Floa extends PaymentMethod
{
    protected $_code = 'paynl_payment_floa';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4815;
    }
}
