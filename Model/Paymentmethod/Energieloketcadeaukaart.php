<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Energieloketcadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_energieloketcadeaukaart';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5313;
    }
}
