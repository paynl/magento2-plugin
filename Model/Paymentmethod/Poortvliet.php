<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Poortvliet extends PaymentMethod
{
    protected $_code = 'paynl_payment_poortvliet';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5172;
    }
}
