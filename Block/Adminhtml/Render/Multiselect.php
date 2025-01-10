<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Multiselect extends Field
{
    protected $configPath;
    protected $_template = 'Paynl_Payment::system/config/multiselect.phtml';

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) // phpcs:ignore
    {
        $this->configPath = $element->getData('field_config')['config_path'];
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        $this->setData('element', $element);

        return $this->_toHtml();
    }
}
