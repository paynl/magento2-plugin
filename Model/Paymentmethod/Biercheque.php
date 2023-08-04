<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Biercheque extends PaymentMethod
{
    protected $_code = 'paynl_payment_biercheque';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 2622;
    }
}
