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
use Magento\Payment\Model\Method\AbstractMethod;
/**
 * Description of Abstract
 *
 * @author Andy Pieters <andy@pay.nl>
 */
abstract class AbstractPaymentmethod extends AbstractMethod{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = true;
    
    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseCheckout = true;


    /**
     * Payment additional info block
     *
     * @var string
     */
//    protected $_formBlockType = 'Magento\SamplePaymentProvider\Block\Form\Payinstore';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * Set order state and status
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     */
//    public function isAvailable($quote = null)
//    {
//        if ($quote === null) {
//            return false;
//        }
//        return parent::isAvailable($quote) && $this->isCarrierAllowed(
//            $quote->getShippingAddress()->getShippingMethod()
//        );
//    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
    }
}
