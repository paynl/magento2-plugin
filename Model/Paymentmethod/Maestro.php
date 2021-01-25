<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Maestro
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Maestro extends \Paynl\Payment\Model\Paymentmethod\PaymentMethod
{
    protected $_code = 'paynl_payment_maestro';

    protected function getDefaultPaymentOptionId()
    {
        return 712;
    }
}