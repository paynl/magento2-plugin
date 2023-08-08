<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;
use Paynl\Payment\Model\Config;

class Version extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Paynl_Payment::system/config/versioncheck.phtml';

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Config
     */
    protected $paynlConfig;

    /**
     * @param Context $context
     * @param Config $paynlConfig
     * @param UrlInterface $urlInterface
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paynlConfig,
        UrlInterface $urlInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paynlConfig = $paynlConfig;
        $this->urlInterface = $urlInterface;
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
    public function _getElementHtml(AbstractElement $element)  // phpcs:ignore
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
        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData(['id' => 'paynl_version_check_button', 'label' => __('Check version')]);
        return $button->toHtml();
    }

    /**
     * @return string
     */
    public function getFeatureRequestUrl()
    {
        $currentUrl = $this->urlInterface->getCurrentUrl();
        $payUrl = str_replace("paynl_setup", "paynl_feature_request", $currentUrl);
        return $payUrl;
    }
}
