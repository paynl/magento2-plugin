<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Twint extends PaymentMethod
{
    protected $_code = 'paynl_payment_twint';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3840;
    }
}
