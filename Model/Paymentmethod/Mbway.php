<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Mbway extends PaymentMethod
{
    protected $_code = 'paynl_payment_mbway';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3846;
    }
}
