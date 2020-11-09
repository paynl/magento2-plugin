<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Huisentuincadeau
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Huisentuincadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_huisentuincadeau';

    protected function getDefaultPaymentOptionId()
    {
        return 2283;
    }
}
