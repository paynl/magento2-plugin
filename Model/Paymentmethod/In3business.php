<?php

namespace Paynl\Payment\Model\Paymentmethod;

class In3business extends PaymentMethod
{
    protected $_code = 'paynl_payment_in3business';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 3192;
    }
}
