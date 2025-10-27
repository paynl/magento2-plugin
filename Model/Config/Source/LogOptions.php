<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class LogOptions implements ArrayInterface
{
    public const LOG_ALL = 0;
    public const LOG_CRITICAL_NOTICE = 1;
    public const LOG_ONLY_CRITICAL = 2;
    public const LOG_NONE = 3;
    public const LOG_DEV = 4;

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
            self::LOG_NONE => __('No logging'),
            self::LOG_ONLY_CRITICAL => __('Only Critical errors are logged'),
            self::LOG_CRITICAL_NOTICE => __('Only Critical errors and Notices are logged'),
            self::LOG_ALL => __('Everything is logged, including Critical, Notice, Info and Debug'),
            self::LOG_DEV => __('Everything is logged, including Critical, Notice, Info and Debug and Dev messages'),
        ];
    }
}
