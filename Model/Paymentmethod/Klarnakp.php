<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Klarnakp extends PaymentMethod
{
    protected $_code = 'paynl_payment_klarnakp';

    protected function getDefaultPaymentOptionId()
    {
        return 2265;
    }
}
