<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Festivalcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_festivalcadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2511;
    }
}
