<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Paynl\Payment\Model\Config;
use \Paynl\Paymentmethods;

/**
 * Class Version
 *
 */
class Errors extends Field
{
    protected $paynlConfig;

    public function __construct(
        Context $context,
        Config $paynlConfig
    ) {
        parent::__construct($context);
        $this->paynlConfig = $paynlConfig;
    }

    public function error()
    {
        $error = null;
        $apiToken = $this->paynlConfig->getApiToken();
        $serviceId = $this->paynlConfig->getServiceId();
        $tokencode = $this->paynlConfig->getTokencode();
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

        return $error;
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
        if ($this->error()) {
            $html = '<div class="message message-error error"><div data-ui-id="messages-message-error">' . $this->error() . '</div></div>';
            return $html;
        }
        return '';
    }
}
