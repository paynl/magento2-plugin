<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Swish extends PaymentMethod
{
    protected $_code = 'paynl_payment_swish';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3837;
    }
}
