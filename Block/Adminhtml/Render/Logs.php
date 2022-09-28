<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\AuthorizationInterface;

class Logs extends Field
{
    protected $authorization;
    protected $_template = 'Paynl_Payment::system/config/logs.phtml';
    public function __construct(Context $context, AuthorizationInterface $authorization, array $data = [])
    {
        $this->authorization = $authorization;
        parent::__construct($context, $data);
    }

    protected function _isAllowed()
    {
        return $this->authorization->isAllowed('Paynl_Payment::logs');
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        if (!$this->_isAllowed()) {
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_logs_download', 'label' => __('Download Logs'), 'disabled' => 'disabled']);
        } else {
            $url = $this->getUrl('paynl/order/logs');
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_logs_download', 'label' => __('Download Logs'), 'onclick' => 'setLocation(\'' . $url . '\')']);
        }
        return $button->toHtml();
    }
}
