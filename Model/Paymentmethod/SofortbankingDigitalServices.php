<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class SofortbankingDigitalServices
 * @package Paynl\Payment\Model\Paymentmethod
 */
class SofortbankingDigitalServices extends PaymentMethod
{
    protected $_code = 'paynl_payment_sofortbanking_ds';

    protected function getDefaultPaymentOptionId()
    {
        return 577;
    }
}
