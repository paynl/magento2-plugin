<?php

namespace Paynl\Payment\Model\Paymentmethod;

use Paynl\Payment\Model\Config;

class Doenkadoticket extends PaymentMethod
{
    protected $_code = 'paynl_payment_doenkadoticket';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 5340;
    }
}
