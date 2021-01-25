<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Wijncadeau
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Wijncadeau extends PaymentMethod
{
    protected $_code = 'paynl_payment_wijncadeau';

    protected function getDefaultPaymentOptionId()
    {
        return 1666;
    }
}