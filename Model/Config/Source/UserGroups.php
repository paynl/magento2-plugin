<?php

namespace Paynl\Payment\Model\Config\Source;

use \Magento\Framework\Option\ArrayInterface;

class UserGroups implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();  
        $groupOptions = $objectManager->get(\Magento\Customer\Model\ResourceModel\Group\Collection::class);
        $groups = $groupOptions->toOptionArray();
        array_unshift($groups, array(
            'value' => '',
            'label' => __('All')
        ));
        return $groups;
    }
}
