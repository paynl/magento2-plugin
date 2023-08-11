<?php

namespace Paynl\Payment\Model\Paymentmethod;

/**
 * Invisible payment method.
 * Needs to be set as a model for the PAY. basic settings, because a model is required
 * Doesn't do anything
 */
class Invisible extends PaymentMethod
{
    protected $_canUseCheckout = false;
    protected $_canUseInternal = false;
    protected $_code = 'paynl_payment_invisible';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 0;
    }
}
