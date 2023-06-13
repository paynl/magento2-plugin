<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\Option\ArrayInterface;

class UserGroups implements ArrayInterface
{
    /**
     * @var Collection
     */
    protected $groupOptions;

    /**
     * constructor.
     * @param Collection $groupOptions
     */
    public function __construct(
        Collection $groupOptions
    ) {
        $this->groupOptions = $groupOptions;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $groups = $this->groupOptions->toOptionArray();
        array_unshift($groups, array(
            'value' => '',
            'label' => __('All'),
        ));
        return $groups;
    }
}
