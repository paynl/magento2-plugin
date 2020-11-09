<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Mistercash
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Mistercash extends PaymentMethod
{
    protected $_code = 'paynl_payment_mistercash';

    protected function getDefaultPaymentOptionId()
    {
        return 436;
    }
}