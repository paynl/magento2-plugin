<?php
/**
 * Copyright © 2020 PAY. All rights reserved.
 */

namespace Paynl\Payment\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

/**
 * Order Status source model
 */
class Processing extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [Order::STATE_PROCESSING];
}
