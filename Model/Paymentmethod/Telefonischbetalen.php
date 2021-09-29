<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Telefonischbetalen extends PaymentMethod
{
    protected $_code = 'paynl_payment_telefonischbetalen';

    protected function getDefaultPaymentOptionId()
    {
        return 1600;
    }
}
