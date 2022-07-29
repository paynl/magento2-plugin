<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SettingsButton extends Field
{
    public function __construct(
        Context $context
    ) {
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
        $urlInterface = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\UrlInterface::class);
        $currentUrl = $urlInterface->getCurrentUrl();
        $payUrl = str_replace("payment", "paynl_general", $currentUrl);

        $textTranslated = __('PAY. Settings have been moved to their own tab, click [[here]] to go to the new settings page.');
        $text = str_replace('[[here]]', '<a href="' . $payUrl . '">here</a>', $textTranslated);
        $text = str_replace('[[hier]]', '<a href="' . $payUrl . '">hier</a>', $textTranslated);

        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="value" style="width:100%;">' . $text . '</td>';
        $html .= '</tr>';

        return $html;
    }
}
