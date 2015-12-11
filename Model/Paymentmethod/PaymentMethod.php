<?php
/*
 * Copyright (C) 2015 Andy Pieters <andy@pay.nl>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Paynl\Payment\Model\Paymentmethod;

use \Magento\Payment\Model\Method\AbstractMethod;
/**
 * Description of AbstractPaymentMethod
 *
 * @author Andy Pieters <andy@pay.nl>
 */
abstract class PaymentMethod extends AbstractMethod
{
    protected $_isInitializeNeeded = true;

    protected $_canRefund = false;
//    protected $_formBlockType = 'Paynl\Payment\Block\Form\Default';
    /**
     * Sidebar payment info block
     *
     * @var string
     */
    //protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }


    public function initSettings()
    {

        $storeId = $this->getStore();

        $apitoken  = $this->_scopeConfig->getValue('payment/paynl/apitoken',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $serviceId = $this->_scopeConfig->getValue('payment/paynl/serviceid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        \Paynl\Config::setApitoken($apitoken);
        \Paynl\Config::setServiceId($serviceId);
    }

    public function getPaymentOptionId(){
        return $this->getConfigData('payment_option_id');
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

}