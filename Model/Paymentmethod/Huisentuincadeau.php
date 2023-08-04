<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Huisentuincadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_huisentuincadeau';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2283;
    }
}
