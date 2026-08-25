<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Metjebank extends PaymentMethod
{
    protected $_code = 'paynl_payment_metjebank';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5358;
    }
}
