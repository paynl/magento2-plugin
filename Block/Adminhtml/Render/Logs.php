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

    /**
     * @param Context $context
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(Context $context, AuthorizationInterface $authorization, array $data = [])
    {
        $this->authorization = $authorization;
        parent::__construct($context, $data);
    }

    /**
     * @return boolean
     */
    protected function _isAllowed() // phpcs:ignore
    {
        return $this->authorization->isAllowed('Paynl_Payment::logs');
    }

    /**
     * @param AbstractElement $element
     * @return object
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) // phpcs:ignore
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getButtonHtml()
    {
        if (!$this->_isAllowed()) {
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_logs_download', 'label' => __('Download Logs'), 'disabled' => 'disabled']);
        } else {
            $url = $this->getUrl('paynl/order/logs');
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_logs_download', 'label' => __('Download Logs'), 'onclick' => 'setLocation(\'' . $url . '\')']); // phpcs:ignore
        }
        return $button->toHtml();
    }
}
