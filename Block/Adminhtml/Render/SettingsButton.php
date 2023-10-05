<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class SettingsButton extends Field
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
        $payUrl = str_replace("payment", "paynl_setup", $currentUrl);

        $text = __('Pay. - Settings have been moved to their own tab, click') . ' <a href="' . $payUrl . '">' . __('here') . '</a> ' . __('to go to the new settings page.');

        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="value" style="width:100%;">' . $text . '</td>';
        $html .= '</tr>';
        return $html;
    }
}
