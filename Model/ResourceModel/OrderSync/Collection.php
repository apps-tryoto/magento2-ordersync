<?php
/**
 * Oto OrderSync
 *
 * Synchronizes orders to OTO platform.
 *
 * Copyright (C) 2024 Oto <info@tryoto.com>
 *
 * @package Oto_OrderSync
 * @copyright Copyright (c) 2024 Oto (http://www.tryoto.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Oto <info@tryoto.com>
 */

namespace Oto\OrderSync\Model\ResourceModel\OrderSync;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Oto\OrderSync\Model\OrderSync as OrderSync;
use Oto\OrderSync\Model\ResourceModel\OrderSync as OrderSyncResource;

#[\AllowDynamicProperties]
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'job_id';

    protected function _construct()
    {
        $this->_init(OrderSync::class, OrderSyncResource::class);
    }
}