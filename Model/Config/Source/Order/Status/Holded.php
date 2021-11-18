<?php

namespace Paynl\Payment\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

class Holded extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [Order::STATE_HOLDED];
}
