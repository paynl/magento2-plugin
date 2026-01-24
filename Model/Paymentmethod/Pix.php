<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Pix extends PaymentMethod
{
    protected $_code = 'paynl_payment_pix';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4803;
    }
}
