<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Festivalcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_festivalcadeaukaart';

    /**
     * @return int
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2511;
    }
}
