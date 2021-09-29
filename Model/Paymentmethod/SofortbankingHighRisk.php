<?php

namespace Paynl\Payment\Model\Paymentmethod;

class SofortbankingHighRisk extends PaymentMethod
{
    protected $_code = 'paynl_payment_sofortbanking_hr';

    protected function getDefaultPaymentOptionId()
    {
        return 595;
    }
}
