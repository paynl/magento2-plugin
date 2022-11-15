<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Paynl\Payment\Model\Config;

class Version extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Paynl_Payment::system/config/versioncheck.phtml';

    protected $paynlConfig;

    public function __construct(
        Context $context,
        Config  $paynlConfig,
        array   $data = []
    )
    {
        parent::__construct($context, $data);
        $this->paynlConfig = $paynlConfig;
    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     * @return mixed
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return mixed
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('paynl/action/versioncheck');
    }

    /**
     * @return mixed|string
     */
    public function getVersion()
    {
        return $this->paynlConfig->getVersion();
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'version', 'label' => __('Check the latest PAY. plugin version')]);
        return $button->toHtml();
    }
}
