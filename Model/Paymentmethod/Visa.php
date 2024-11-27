<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Visa extends PaymentMethod
{
    protected $_code = 'paynl_payment_visa';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3141;
    }
}
