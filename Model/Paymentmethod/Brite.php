<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Brite extends PaymentMethod
{
    protected $_code = 'paynl_payment_brite';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4287;
    }
}
