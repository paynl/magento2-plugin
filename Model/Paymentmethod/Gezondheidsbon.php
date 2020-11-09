<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Gezondheidsbon
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Gezondheidsbon extends PaymentMethod
{
    protected $_code = 'paynl_payment_gezondheidsbon';

    protected function getDefaultPaymentOptionId()
    {
        return 812;
    }
}