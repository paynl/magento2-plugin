<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Profuomo extends PaymentMethod
{
    protected $_code = 'paynl_payment_profuomo';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4626;
    }
}
