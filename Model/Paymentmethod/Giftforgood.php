<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Giftforgood extends PaymentMethod
{
    protected $_code = 'paynl_payment_giftforgood';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4773;
    }
}
