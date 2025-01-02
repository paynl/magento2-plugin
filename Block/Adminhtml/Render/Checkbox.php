<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Checkbox extends Field
{
    protected $configPath;
    protected $_template = 'Paynl_Payment::system/config/checkbox.phtml';

    private $defaultTrue = [
        'fast_checkout_show_modal'
    ];

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) // phpcs:ignore
    {
        $this->configPath = $element->getData('field_config')['config_path'];
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());
        return $this->_toHtml();
    }

    /**
     *
     * @return boolean
     */
    public function getIsChecked()
    {
        $data = $this->getConfigData();
        if (isset($data[$this->configPath])) {
            $data = $data[$this->configPath];
        } else {
            $data = '';
        }

        if ($this->isDefaultTrue($this->configPath)) {
            return $data != 0;
        } else {
            return $data == 1;
        }
    }

    function isDefaultTrue($configPath)
    {
        foreach ($this->defaultTrue as $default) {
            if (stripos($configPath, $default) !== false)
                return true;
        }
        return false;
    }
}
