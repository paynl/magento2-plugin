<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Visamastercard
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Visamastercard extends PaymentMethod
{
    protected $_code = 'paynl_payment_visamastercard';

    protected function getDefaultPaymentOptionId()
    {
        return 706;
    }
}