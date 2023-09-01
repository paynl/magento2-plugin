<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Good4fun extends PaymentMethod
{
    protected $_code = 'paynl_payment_good4fun';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2628;
    }
}
