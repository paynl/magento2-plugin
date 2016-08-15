<?php
/**
 * Copyright © 2015 Pay.nl All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

/**
 * Order Status source model
 */
class Holded extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [Order::STATE_HOLDED];
}
