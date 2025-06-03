<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Beautyenmorecadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_beautyenmorecadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4173;
    }
}
