<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Incasso extends PaymentMethod
{
    protected $_code = 'paynl_payment_incasso';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 137;
    }
}
