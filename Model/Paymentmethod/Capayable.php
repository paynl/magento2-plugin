<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Description of Ideal
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Capayable extends PaymentMethod
{
    protected $_code = 'paynl_payment_capayable';

    protected function getDefaultPaymentOptionId()
    {
        return 1744;
    }

}