<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Yourgift extends PaymentMethod
{
    protected $_code = 'paynl_payment_yourgift';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 1645;
    }
}
