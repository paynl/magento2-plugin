<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Huisdierencadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_huisdierencadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4158;
    }
}
