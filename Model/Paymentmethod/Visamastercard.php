<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Visamastercard extends PaymentMethod
{
    protected $_code = 'paynl_payment_visamastercard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 706;
    }
}
