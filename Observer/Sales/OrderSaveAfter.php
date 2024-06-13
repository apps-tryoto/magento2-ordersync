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

namespace Oto\OrderSync\Observer\Sales;

use Oto\OrderSync\Helper\Data as QueueHelper;
use Oto\OrderSync\Helper\Data as OtoHelper;

#[\AllowDynamicProperties]
class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{

	public $_helper;

    public function __construct(
        OtoHelper $_helper
    ) 
	{
        $this->_helper = $_helper;
    }
	
	/**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) 
	{

		$order = $observer->getOrder();
		if (!is_object($order) or $order->getId() < 1) 
		{
			$objectManager->get('Psr\Log\LoggerInterface')->info('OTO ORDERSYNC ORDER NOT LOADED');
			return;
		} // if sonu

		$helper = $this->_helper;
		$objectManager = $helper->getObjectManager();
		$debug = $helper->getConfig('oto_ordersync/debug/is_active');
		$startingOrderId = intval(trim(''.$helper->getConfig('oto_ordersync/debug/is_active')));
		$startingOrderDate = $helper->getConfig('oto_ordersync/debug/is_active');

		if ($debug == 1) 
		{
			$objectManager->get('Psr\Log\LoggerInterface')->info('OTO ORDERSYNC JOB TRIGGER TRIGGERED -- OID:'.$order->getId());
		} // if sonu

		$states_arr		= ['processing', 'canceled'];
		$statuses_arr	= ['processing', 'canceled','cod_validated'];

		$sync_all = $helper->getConfig('oto_ordersync/order_settings/sync_all_orders');

		if ($sync_all == 1) 
		{

			/// Send all orders, do not check order payment placed...
			$states_arr		= [ 'processing', 'pending', 'pending_payment', 'canceled' ];
			$statuses_arr	= [ 'processing', 'pending', 'pending_payment', 'canceled' ];

			/// Do not sync credit card orders waiting to payment completion
			if ( substr($order->getPayment()->getMethod(),0,strlen('grinet_')) == 'grinet_'  and in_array($order->getState(),['new']) )
			{
				if ($debug == 1) 
				{
					$objectManager->get('Psr\Log\LoggerInterface')->info('ORDER IS NOT IN EXPECTED STATE/STATUS (STATE/ALL SYNC) -- STATE:'.$order->getState().' -- STATUS:'.$order->getStatus());
				} // if sonu
				return false;
			} // if sonu

			$skip_states_arr	= [ ];
			$skip_statuses_arr	= [ 'grpay_pending_3d' ];

			if ( in_array($order->getStatus(),$skip_statuses_arr) or in_array($order->getState(),$skip_states_arr) )
			{
				if ($debug == 1) 
				{
					$objectManager->get('Psr\Log\LoggerInterface')->info('ORDER IS NOT IN EXPECTED STATE/STATUS (STATUS/ALL SYNC) -- STATE:'.$order->getState().' -- STATUS:'.$order->getStatus());
				} // if sonu
				return false;
			} // if sonu

		} // if sonu
		

		if ($order->getStatus() != 'cod_validated' and !in_array($order->getState(),$states_arr) and !in_array($order->getStatus(),$statuses_arr)) 
		{
			if ($debug == 1) 
			{
				$objectManager->get('Psr\Log\LoggerInterface')->info('ORDER IS NOT IN EXPECTED STATE/STATUS (STATE + STATUS) -- STATE:'.$order->getState().' -- STATUS:'.$order->getStatus());
			} // if sonu
			return false;
		} // if sonu

		$job_status = 'new_order';

		switch($order->getState()){
			case	"canceled":		
							$job_status = 'cancel_order';
							break;
			default :		
							break;
		}
		
		switch($order->getStatus()){
			case	"cod_validated":		
							$job_status = 'cod_validated';
							break;
			default :		
							break;
		}
		

		$q = $objectManager->create('Oto\OrderSync\Model\OrderSync');
		$coll = $q->getCollection();
		$coll->addFieldToFilter('job_type'   , array('eq'=> $job_status));
		$coll->addFieldToFilter('job_target' , array('eq'=> 'oto'));
		$coll->addFieldToFilter('order_id'   , array('eq'=> $order->getId()));
		$coll->setPageSize(1);
		$coll->load();

		if ($coll->count() < 1) 
		{
			$order_queue_arr = [
							'q_id'                 => null,
							'job_type'             => $job_status,
							'job_target'           => 'oto',
							'order_id'             => $order->getId(),
							'order_increment_id'   => $order->getIncrementId(),
							'customer_id'          => $order->getCustomerId(),
							'customer_name'        => $order->getCustomerFirstname()." ".$order->getCustomerLastname(),
							'customer_email'       => $order->getCustomerEmail(),
							'created_at'           => date('Y-m-d H:i:s'),
							'updated_at'           => null,
							'job_status'           => 'WAITING',
							'error_count'          => null,
							'ret_order_id'         => null,
							'ret_account_id'       => null,
							'last_error_msg'       => null,
							];
			$q->setData($order_queue_arr)->save();

			if ($debug == 1) 
			{
				$objectManager->get('Psr\Log\LoggerInterface')->info('JOB ADDED -- ID:'.$q->getId()." -- OrderState : ".$order->getState()." -- OrderStatus : ".$order->getStatus());
			} // if sonu

		} // if sonu		
		else 
		{
			if ($debug == 1) 
			{
				$objectManager->get('Psr\Log\LoggerInterface')->info('JOB ALREADY EXISTS -- ID:'.$q->getId());
			} // if sonu
		} // else sonu
		
	}
}
