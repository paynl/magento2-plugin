<?php

namespace Paynl\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class InvoiceCreation extends PayOption
{
    public function __construct($options = array())
    {
        parent::__construct([
            '1' => __('Create invoice upon payment (default)'),
            '0' => __('Create invoice upon shipment'),
        ]);
    }

}
