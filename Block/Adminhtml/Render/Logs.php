<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Logs extends Field
{
    protected $_template = 'Paynl_Payment::system/config/logs.phtml';
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
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
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_logs_download', 'label' => __('Download Logs'), 'onclick' => 'setLocation(\'/paynl/order/logs/\')']);
        return $button->toHtml() ;
    }
}