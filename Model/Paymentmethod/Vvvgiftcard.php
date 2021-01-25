<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Vvvgiftcard
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Vvvgiftcard extends PaymentMethod
{
    protected $_code = 'paynl_payment_vvvgiftcard';

    protected function getDefaultPaymentOptionId()
    {
        return 1714;
    }
}