<?php

namespace Paynl\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Multiselect extends Field
{
    protected $configPath;

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
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $isCheckboxRequired = $this->_isInheritCheckboxRequired($element);
        if ($element->getInherit() == 1 && $isCheckboxRequired) {
            $element->setDisabled(true);
        }

        if ($element->getIsDisableInheritance()) {
            $element->setReadonly(true);
        }

        $html = '<td class="label"><label for="' .
            $element->getHtmlId() . '"><span' .
            $this->_renderScopeLabel($element) . '>' .
            $element->getLabel() .
            '</span></label></td>';
        $html .= '<td class="value ' . ($element->getTooltip() ? 'with-tooltip' : '') . '">
            <span class="multiselectPay">
                <input type="hidden" id="' . $element->getHtmlId() . '" name="' . $element->getName() . '" value="' . $element->getValue() . '" />        
                <span class="ms_options">';

        foreach ($element->getValues() as $key => $value) {
            if (!empty($value) && !empty($value['value'])) {
                if (isset($value['is_region_visible']) && $value['is_region_visible'] === false) {
                    continue;
                }
                if (is_array($value['value'])) {
                    $label = $value['label'];
                    $multiValues = $value['value'];
                    $html .= '<optgroup label="' . $label . '">';
                    foreach ($multiValues as $key => $multiValue) {
                        $html .= '<option class="' . ((in_array($multiValue['value'], explode(',', $element->getValue() ?? ''))) ? 'selected' : '') . '" value="' . $multiValue['value'] . '">' . $multiValue['label'] . '</option>';
                    }
                    $html .= '</optgroup>';
                } else {
                    $html .= '<option class="' . ((in_array($value['value'], explode(',', $element->getValue() ?? ''))) ? 'selected' : '') . '" value="' . $value['value'] . '">' . $value['label'] . '</option>';
                }
            }
        }

        $html .= '  
                </span>
            </span>';

        if ($element->getTooltip()) {
            $html .= '<div class="tooltip"><span class="help"><span></span></span>';
            $html .= '<div class="tooltip-content">' . $element->getTooltip() . '</div></div>';
        }

        if ($element->getComment()) {
            $html .= '<p class="note"><span>' . $element->getComment() . '</span></p>';
        }

        $html .= '</td>';

        if ($isCheckboxRequired) {
            $html .= $this->_renderInheritCheckbox($element);
        }

        $html .= $this->_renderHint($element);

        return $this->_decorateRowHtml($element, $html);
    }

}
