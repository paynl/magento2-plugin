<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Riverty extends PaymentMethod
{
    protected $_code = 'paynl_payment_riverty';

    protected function getDefaultPaymentOptionId()
    {
        return 2561;
    }
}
