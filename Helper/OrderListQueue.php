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

namespace Oto\OrderSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

#[\AllowDynamicProperties]
class OrderListQueue extends \Oto\OrderSync\Helper\Data
{

	// -------------------------------------------------------------------------------------------------------
	public function listPendingOrders() {

		$this->debug = $this->getConfig('oto_ordersync/debug/is_active');

		$this->_objectManager = $this->getObjectManager();

		$q =  $this->_objectManager->get('Oto\OrderSync\Model\OrderSync');
		$order_jobs = $q->getCollection();
		$order_jobs->getSelect()->where("( job_status in('WAITING','IN_PROGRESS','ERROR'))");

		if ($order_jobs->count() < 1) 
		{
			echo date("Y-m-d H:i:s").__('No order job to sync...')."\n";
			return;
		} // if sonu

		$ret_orders = [];
		foreach ($order_jobs as $job) 
		{
			if (intval($job['order_id']) < 1) {continue;} // if sonu
			
			$order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($job['order_id']);
			$ret_orders[] = [
								'entity_id'			=> $order->getId(),	
								'increment_id'		=> $order->getIncrementId(),	
								'customer_name'		=> $order->getCustomerName(),	
								'customer_email'	=> $order->getCustomerEmail(),	
								'grand_total'		=> number_format($order->getGrandTotal(),2),	
								'status'			=> $job['job_status'],	
								'error_count'		=> $job['error_count'],	
							];
		} // foreach sonu

		return $ret_orders;

	} // function sonu ---------------------------------------------------------------------------------------

}
