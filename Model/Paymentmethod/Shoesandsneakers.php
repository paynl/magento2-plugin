<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Shoesandsneakers extends PaymentMethod
{
    protected $_code = 'paynl_payment_shoesandsneakers';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2937;
    }
}
