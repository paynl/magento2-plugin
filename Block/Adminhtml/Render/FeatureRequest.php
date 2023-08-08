<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Paynl\Payment\Model\Config;

class FeatureRequest extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Paynl_Payment::system/config/featurerequest.phtml';

    protected $paynlConfig;

    public function __construct(
        Context $context,
        Config $paynlConfig,
        array $data = []
    ) {
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
        return $this->getUrl('paynl/action/featurerequest');
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_submit_email_feature_request', 'label' => __('Submit')]);
        return $button->toHtml();
    }

    /**
     * @return mixed|string
     */
    public function getVersion()
    {
        return $this->paynlConfig->getVersion();
    }

    /**
     * @return mixed|string
     */
    public function getMagentoVersion()
    {
        return $this->paynlConfig->getMagentoVersion();
    }

}
