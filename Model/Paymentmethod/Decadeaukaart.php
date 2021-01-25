<?php

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Decadeaukaart
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Decadeaukaart extends PaymentMethod
{
    protected $_code = 'paynl_payment_decadeaukaart';

    protected function getDefaultPaymentOptionId()
    {
        return 2601;
    }
}