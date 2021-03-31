<?php
namespace Paynl\Payment\Model\Paymentmethod;
/**
 * Class Focum
 * @package Paynl\Payment\Model\Paymentmethod
 */
class Focum extends PaymentMethod
{
    protected $_code = 'paynl_payment_focum';

    protected function getDefaultPaymentOptionId()
    {
        return 1702;
    }

    public function getDOB()
    {
        return $this->_scopeConfig->getValue('payment/paynl_payment_focum/showdob', 'store');
    }
}