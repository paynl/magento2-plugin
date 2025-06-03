<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Satispay extends PaymentMethod
{
    protected $_code = 'paynl_payment_satispay';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4146;
    }
}
