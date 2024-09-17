<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Flyingblueplus extends PaymentMethod
{
    protected $_code = 'paynl_payment_flyingblueplus';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3615;
    }
}
