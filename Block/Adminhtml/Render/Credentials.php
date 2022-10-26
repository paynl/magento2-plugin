<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Paynl\Paymentmethods;

class Credentials extends Field
{
    /**
     *
     * @var Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     *
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        Context $context,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        $scope = 'default';
        $scopeId = 0;

        if ($storeId) {
            $scope = 'stores';
            $scopeId = $storeId;
        }
        if ($websiteId) {
            $scope = 'websites';
            $scopeId = $websiteId;
        }

        $tokencode = $this->scopeConfig->getValue('payment/paynl/tokencode', $scope, $scopeId);
        $apiToken = $this->scopeConfig->getValue('payment/paynl/apitoken', $scope, $scopeId);
        $serviceId = $this->scopeConfig->getValue('payment/paynl/serviceid', $scope, $scopeId);
        $gateway = $this->scopeConfig->getValue('payment/paynl/failover_gateway', $scope, $scopeId);

        $error = null;
        $status = 1;
        if (!empty($apiToken) && !empty($serviceId) && !empty($tokencode)) {
            try {
                if (!empty($gateway) && substr(trim($gateway), 0, 4) === "http") {
                    \Paynl\Config::setApiBase(trim($gateway));
                }
                \Paynl\Config::setTokenCode($tokencode);
                \Paynl\Config::setApiToken($apiToken);
                \Paynl\Config::setServiceId($serviceId);

                Paymentmethods::getList();
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } elseif (!empty($apiToken) || !empty($serviceId) || !empty($tokencode)) {
            $error = __('Pay. Tokencode, API token and serviceId are required.');
        }

        switch ($error) {
            case 'HTTP/1.0 401 Unauthorized':
                $error = __('Service-ID, API-Token or Tokencode invalid');
                break;
            case 'PAY-404 - Service not found':
                $error = __('Service-ID is invalid.');
                break;
            case 'PAY-403 - Access denied: Token not valid for this company':
                $error = __('Service-ID / API-Token combination is invalid.');
                break;
        }

        if (!empty($error)) {
            $status = 0;
        }

        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="label">' . $element->getData('label') . '</td>';
        if ($status) {
            $html .= '  <td class="value" style="color:#10723a; font-weight: bold">' . __('Pay. Successfully connected.') . '</td>';
        } elseif (!empty($error)) {
            $html .= '  <td class="value" style="color:#f00; font-weight: bold">' . __('Pay. Connection failed.') . ' (' . $error . ')</td>';
        } else {
            $html .= '  <td class="value" style="color:#ff8300; font-weight: bold">' . __('Pay. Not connected.') . '</td>';
        }
        $html .= '  <td></td>';
        $html .= '</tr>';

        return $html;
    }
}
