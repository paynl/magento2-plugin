<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Webshopgiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_webshopgiftcard';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 811;
    }
}
