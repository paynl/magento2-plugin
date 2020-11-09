<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Paysafecard
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Paysafecard extends PaymentMethod
{
    protected $_code = 'paynl_payment_paysafecard';

    protected function getDefaultPaymentOptionId()
    {
        return 553;
    }
}