<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class SofortbankingHighRisk
 * @package Paynl\Payment\Model\Paymentmethod
 */
class SofortbankingHighRisk extends PaymentMethod
{
    protected $_code = 'paynl_payment_sofortbanking_hr';

    protected function getDefaultPaymentOptionId()
    {
        return 595;
    }
}
