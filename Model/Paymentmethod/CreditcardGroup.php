<?php

namespace Paynl\Payment\Model\Paymentmethod;

class CreditcardGroup extends PaymentMethod
{
    protected $_code = 'paynl_payment_creditcardgroup';

    protected function getDefaultPaymentOptionId()
    {
        return 11;
    }
}