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
            'nl' => __('Nederlands'),
            'en' => __('English'),
            'de' => __('Deutsch'),
            'it' => __('Italiano'),
            'fr' => __('Francais'),
            'es' => __('Español'),
            'dk' => __('Dansk'),
            'pl' => __('Polski'),
            'us' => __('American'),
            'mx' => __('Mexicano'),
            'hu' => __('Magyar'),
            'no' => __('Norsk'),
            'hr' => __('Hrvatski'),
            'pt' => __('Português'),
            'ro' => __('Română'),
            'sv' => __('Svenska'),
            'sl' => __('Slovenski'),
            'tr' => __('Türk'),
            'fi' => __('Suomalainen'),
            'cz' => __('Česky'),
            'gr' => __('Ελληνικά'),
            'jp' => __('日本語'),
            'browser' => __('Use browser language'),
            'website' => __('Use website language')
        ];
    }
}
