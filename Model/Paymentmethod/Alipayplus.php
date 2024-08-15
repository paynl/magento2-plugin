<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Alipayplus extends PaymentMethod
{
    protected $_code = 'paynl_payment_alipayplus';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2907;
    }
}
