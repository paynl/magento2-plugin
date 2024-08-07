<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class CacheButton extends Field
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * constructor.
     * @param Context $context
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
        parent::__construct($context);
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
        $currentUrl = $this->urlInterface->getCurrentUrl();
        $payUrl = str_replace("system_config/edit/section/paynl_paymentmethods", "cache", $currentUrl);

        $text = __('When updating this setting, please flush Magento\'s cache afterwards ') . ' <a href="' . $payUrl . '">' . __('here') . '</a>.';
        $html = '<tr id="row_' . $element->getHtmlId() . '" class="PaynlCacheButton">';
        $html .= '<td></td><td class="value">' . $text . '</td>';
        $html .= '</tr>';
        return $html;
    }
}
