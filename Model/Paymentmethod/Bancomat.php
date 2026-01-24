<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Bancomat extends PaymentMethod
{
    protected $_code = 'paynl_payment_bancomat';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 4809;
    }
}
