<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Telefonischbetalen
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Telefonischbetalen extends PaymentMethod
{
    protected $_code = 'paynl_payment_telefonischbetalen';

    protected function getDefaultPaymentOptionId()
    {
        return 1600;
    }
}