<?php
/*
 * Copyright (C) 2015 Pay.nl
 */

namespace Paynl\Payment\Model;

/**
 * Description of Config
 *
 * @author Andy Pieters <andy@pay.nl>
 */
class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfigInterface;

    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $configInterface
    )
    {
        $this->_scopeConfigInterface = $configInterface;
    }

    public function getApiToken()
    {
        return $this->_scopeConfigInterface->getValue('payment/paynl/apitoken');
    }

    public function getServiceId()
    {
        return $this->_scopeConfigInterface->getValue('payment/paynl/serviceid');
    }

    public function isTestMode()
    {
       return $this->_scopeConfigInterface->getValue('payment/paynl/testmode') == 1;
    }
}