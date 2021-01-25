<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Cartebleue
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Cartebleue extends PaymentMethod
{
    protected $_code = 'paynl_payment_cartebleue';

    protected function getDefaultPaymentOptionId()
    {
        return 710;
    }
}