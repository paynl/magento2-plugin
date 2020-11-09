<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Overboeking
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Overboeking extends PaymentMethod
{
    protected $_code = 'paynl_payment_overboeking';

    protected function getDefaultPaymentOptionId()
    {
        return 136;
    }
}