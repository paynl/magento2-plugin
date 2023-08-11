<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Cartebleue extends PaymentMethod
{
    protected $_code = 'paynl_payment_cartebleue';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 710;
    }
}
