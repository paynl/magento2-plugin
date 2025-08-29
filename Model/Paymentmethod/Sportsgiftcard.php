<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Sportsgiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_sportsgiftcard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4422;
    }
}
