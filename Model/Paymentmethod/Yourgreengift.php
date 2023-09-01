<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Yourgreengift extends PaymentMethod
{
    protected $_code = 'paynl_payment_yourgreengift';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2925;
    }
}
