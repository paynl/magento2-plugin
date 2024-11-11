<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Mastercard extends PaymentMethod
{
    protected $_code = 'paynl_payment_mastercard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3138;
    }
}
