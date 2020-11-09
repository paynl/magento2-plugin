<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Giropay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Giropay extends PaymentMethod
{
    protected $_code = 'paynl_payment_giropay';

    protected function getDefaultPaymentOptionId()
    {
        return 694;
    }
}