<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;
/**
 * Class Dankort
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Dankort extends PaymentMethod
{
    protected $_code = 'paynl_payment_dankort';

    protected function getDefaultPaymentOptionId()
    {
        return 1939;
    }
}