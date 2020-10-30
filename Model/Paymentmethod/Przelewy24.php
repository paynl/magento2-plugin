<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Przelewy24
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Przelewy24 extends PaymentMethod
{
    protected $_code = 'paynl_payment_przelewy24';

    protected function getDefaultPaymentOptionId()
    {
        return 2151;
    }
}
