<?php
/**
 * Copyright © 2021 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Amazonpay
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Amazonpay extends PaymentMethod
{
    protected $_code = 'paynl_payment_amazonpay';

    protected function getDefaultPaymentOptionId()
    {
        return 1903;
    }
}