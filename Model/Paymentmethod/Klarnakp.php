<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Class Klarnakp
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Klarnakp extends PaymentMethod
{
    protected $_code = 'paynl_payment_klarnakp';

    protected function getDefaultPaymentOptionId()
    {
        return 2265;
    }

    public function getDOB()
    {
        return $this->_scopeConfig->getValue('payment/paynl_payment_klarnakp/showdob', 'store');
    }
}
