<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Sofortbanking extends PaymentMethod
{
    protected $_code = 'paynl_payment_sofortbanking';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 559;
    }
}
