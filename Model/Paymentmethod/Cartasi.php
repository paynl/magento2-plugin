<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

class Cartasi extends PaymentMethod
{
    protected $_code = 'paynl_payment_cartasi';

    protected function getDefaultPaymentOptionId()
    {
        return 1945;
    }
}