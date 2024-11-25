<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Languages implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptions = $this->toArray();

        $arrResult = [];
        foreach ($arrOptions as $value => $label) {
            $arrResult[] = ['value' => $value, 'label' => $label];
        }
        return $arrResult;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'nl' => __('Dutch'),
            'en' => __('English'),
            'de' => __('German'),
            'it' => __('Italian'),
            'fr' => __('French'),
            'es' => __('Spanish'),
            'dk' => __('Danish'),
            'pl' => __('Polish'),
            'us' => __('American'),
            'mx' => __('Mexican'),
            'hu' => __('Hungarian'),
            'no' => __('Norwegian'),
            'hr' => __('Croatian'),
            'pt' => __('Portuguese'),
            'ro' => __('Romanian'),
            'sv' => __('Swedish'),
            'sl' => __('Slovenian'),
            'tr' => __('Turkish'),
            'fi' => __('Finnish'),
            'cz' => __('Czech'),
            'gr' => __('Greek'),
            'jp' => __('Japanese'),
            'browser' => __('Use browser language'),
            'website' => __('Use website language')
        ];
    }
}
