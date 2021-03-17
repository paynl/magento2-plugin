<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Soforthighrisk
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Soforthighrisk extends PaymentMethod
{
    protected $_code = 'paynl_payment_soforthighrisk';

    protected function getDefaultPaymentOptionId()
    {
        return 595;
    }
}
