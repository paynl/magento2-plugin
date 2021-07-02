<?php

namespace Paynl\Payment\Model\Paymentmethod;
/**
 * Class Googlepay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Googlepay extends PaymentMethod
{
    protected $_code = 'paynl_payment_googlepay';

    protected function getDefaultPaymentOptionId()
    {
        return 2558;
    }
}