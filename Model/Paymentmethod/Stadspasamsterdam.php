<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Stadspasamsterdam extends PaymentMethod
{
    protected $_code = 'paynl_payment_stadspasamsterdam';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3609;
    }
}
