<?php
namespace Paynl\Payment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template;

class InactiveMethodsButton extends Template
{
    protected function _toHtml()
    {
        $currentSection = $this->getRequest()->getParam('section');
        if ($currentSection !== 'paynl_paymentmethods') {
            return '';
        }

        return parent::_toHtml();
    }
}