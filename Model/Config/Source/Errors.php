<?php

/**
 * Copyright © 2020 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use \Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Method\Factory as PaymentMethodFactory;
use Magento\Store\Model\ScopeInterface;
use \Paynl\Payment\Model\Config;
use Paynl\Payment\Model\Paymentmethod\PaymentMethod;
use \Paynl\Paymentmethods;

class Errors implements ArrayInterface
{
  /**
   * @var string The name of the class for this method
   */
  protected $_class;

  /**
   * @var RequestInterface
   */
  protected $_request;
  /**
   * @var ScopeConfigInterface
   */
  protected $_scopeConfig;

  /**
   * @var Config
   */
  protected $_config;


  public function __construct(
    Config $config,
    RequestInterface $request,
    ScopeConfigInterface $scopeConfig
  ) {
    $this->_config = $config;
    $this->_request = $request;
    $this->_scopeConfig = $scopeConfig;
  }

  public function errors()
  {
    $error = '';
    $apiToken = trim($this->getConfigValue('payment/paynl/apitoken'));
    $serviceId = trim($this->getConfigValue('payment/paynl/serviceid'));
    $tokencode = trim($this->getConfigValue('payment/paynl/tokencode'));

    if (!empty($apiToken) && !empty($serviceId)) {
      \Paynl\Config::setApiToken($apiToken);
      \Paynl\Config::setServiceId($serviceId);
      try {
        $list = Paymentmethods::getList();
      } catch (\Exception $e) {
        $error = $e->getMessage();
      }
    } else if (empty($apiToken) && empty($serviceId)) {
      $error = __('PAY. API token and serviceId are required.');
    } else if (empty($apiToken)) {
      $error = __('PAY. API token is required.');
    } else {
      $error = __('PAY. serviceId is required.');
    }
    switch ($error) {
      case 'HTTP/1.0 401 Unauthorized':
        $error = __('PAY. API token is invalid.');
        break;
      case 'PAY-404 - Service not found':
        $error = __('PAY. serviceId is invalid.');
        break;
      case 'PAY-403 - Access denied: Token not valid for this company':
        $error = __('PAY. Api token / serviceId combination is invalid.');
        break;
    }
    if (!empty($error)) {
      echo '<div class="message message-error error"><div data-ui-id="messages-message-error">' . $error . '</div></div>';
    }
    return $error;
  }

  protected function getConfigValue($path)
  {
    $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    $scopeValue = null;

    $store = $this->_request->getParam('store');
    $website = $this->_request->getParam('website');
    if ($store) {
      $scopeValue = $store;
      $scopeType = ScopeInterface::SCOPE_STORE;
    } elseif ($website) {
      $scopeValue = $website;
      $scopeType = ScopeInterface::SCOPE_WEBSITE;
    }
    return $this->_scopeConfig->getValue($path, $scopeType, $scopeValue);
  }

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray()
  {
    $arrResult[] = ['value' => 'error', 'label' => $this->errors()];
    return $arrResult;
  }
}
