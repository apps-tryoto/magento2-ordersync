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
class OrderSyncNew extends \Oto\OrderSync\Helper\OrderSync
{

	/*
		@function
	*/

	public function syncOrders($params, $output) {

		if (!is_array($params)) 
		{
			$params = [];
		} // if sonu
		
		$this->output = $output;

		$order_inc_ids = $params['order'];
		$order_ids = '';

		$this->api_url = trim(''.$this->getConfig('oto_webhook_url'));
		$this->oto_access_token = trim(''.$this->getConfig('oto_access_token'));
		$this->api_access_secret = trim(''.$this->getConfig('oto_access_secret'));

		if (strlen($this->oto_access_token) < 10 or strlen($this->api_url) < 10) 
		{
			$this->output->writeln('<warning>'. $this->getCurrentDateTime() ." -- ". __('Your system not connected to OTO. Please go to admin page and click to Connect to Oto link on OTO Menu...').'</>')."\n";
			return;
		} // if sonu
		

		$this->debug = $this->getConfig('oto_ordersync/debug/is_active');

		$this->_objectManager = $this->getObjectManager();
		$this->stockItemRepository =  $this->_objectManager->get('\Magento\CatalogInventory\Model\Stock\StockItemRepository');

		$q =  $this->_objectManager->get('\Oto\OrderSync\Model\OrderSync');
		$order_jobs = $q->getCollection();
		$order_jobs->addFieldToFilter('job_type', 'new_order');
		$order_jobs->addFieldToFilter('job_target', 'oto');

		$order_inc_ids		= explode(',',@$params['order'].'');
		$order_ids			= explode(',',@$params['order-id'].'');

		$this->force_order	= @$params['force-order'] == true ? true:false;
		$this->debug_order	= @$params['debug-order'] == true ? true:false;

		if (is_array($order_inc_ids) and count($order_inc_ids) == 1 and $order_inc_ids[0] == '') 
		{
			unset($order_inc_ids[0]);
		} // if sonu
		
		if (is_array($order_ids) and count($order_ids) > 0 and $order_ids[0] == '') 
		{
			unset($order_ids[0]);
		} // else sonu

		if (is_array($order_inc_ids) and count($order_inc_ids) > 0) 
		{
			$order_jobs->getSelect()->where(" order_increment_id in('".join("','",$order_inc_ids)."')");
		} // if sonu
		elseif (is_array($order_ids) and count($order_ids) > 0) 
		{
			$order_jobs->getSelect()->where(" order_id in('".join("','",$order_ids)."')");
		} // else sonu
		else 
		{
			$order_jobs->getSelect()->where(" 
				( job_status in('WAITING','IN_PROGRESS') OR  ( job_status = 'ERROR' AND ( error_count < 100 or error_count IS NULL ) ) ) AND 
				order_id >= ".$this->_order_start_from_order_id)."";
		} // else sonu

		if ($order_jobs->count() < 1) 
		{
			$this->output->writeln('<comment>'. $this->getCurrentDateTime() ." -- ". __('No order job to sync...').'</>')."\n";
			return;
		} // if sonu

		//// Order Loop ////////////////////////////////////////////////////////////////////////////

		foreach ($order_jobs as $job) 
		{
			$this->job = $job;

			$msg = $this->output->writeln('<comment>'.__("Job: %1 -- Order: #%2 -- Order ID: %5 -- Customer: %3 - Cust.Mail: %4 ",
						$job['q_id'],
						$job['order_increment_id'],
						$job['customer_name'],
						$job['customer_email'],
						$job['order_id']
			).'</>');

			echo " -- ".$msg;
			if ($this->debug == 1) 
			{
				$this->_objectManager->get('Psr\Log\LoggerInterface')->info('OTO :'.$msg);
			} // if sonu
			
			$job
				->setData('job_status','IN_PROGRESS')
				->setData('updated_at',$this->getCurrentDateTime())
				->save();

			$this->currentJob = $job;

			$ret = $this->createOrderInTarget($job->getData('order_id'));

			if ($ret['status'] == 'error') 
			{
				$job
					->setData('job_status','ERROR')
					->setData('error_count',$job->getData('error_count')+1)
					->setData('last_error_msg',@$ret['error_msg'])
					->setData('updated_at',$this->getCurrentDateTime())
					->save();

				$this->output->writeln('<error>'. __(' -- Order not sent - %1',@$ret['error_msg']).'</>')."\n";

			} // if sonu
			elseif ($ret['status'] == 'skipped') 
			{
				$job
					->setData('job_status','ERROR')
					//->setData('error_count',$job->getData('error_count')+1)
					->setData('last_error_msg',__('This order already sent'))
					->setData('updated_at',$this->getCurrentDateTime())
					->save();

				$this->output->writeln('<error>'. __(' -- Order skipped : %1',@$ret['error_msg']).'</>')."\n";

			} // if sonu
			elseif ($ret['status'] == 'success') 
			{
				$job
					->setData('job_status','SUCCESS')
					->setData('ret_account_id',@$ret['crmAccountId'])
					->setData('ret_order_id',@$ret['crmOrderId'])
					->setData('updated_at',$this->getCurrentDateTime())
					->save();

				$this->output->writeln('<info>'. __(' -- Order synced : ')." #".@$ret['crmOrderId'].'</>')."\n";
			} // else sonu

			echo "\n";
		
		} // foreach sonu

	} // eof func
	
	/*
		@function
	*/

	public function createOrderInTarget($orderId) {

		if ($orderId < 1) 
		{
			return ['status' => 'error',	'error_msg' => (string) __("Order ID is empty."),	];
		} // if sonu
		
		$order = $this->current_order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);

		if (!is_object($order) or $order->getId() < 1) 
		{
			return ['status' => 'error',	'error_msg' => (string) __("Order can not be loaded."),	];
		} // if sonu

		$shipping_method = $order->getShippingMethod();

		$this->api_website_code	= trim(''.$this->getConfig('oto_ordersync/connection_settings/website_code', $order->getStoreId()));
		
		if ($order->getPayment()->getMethod() == 'free') 
		{
			$orderCode = $this->_order_prefix_zero_total.substr($order->getIncrementId(),-8).$this->_order_suffix;
		} // if sonu
		elseif (in_array($order->getPayment()->getMethod(), $this->_order_prefix_bankpayment_method)) 
		{
			$orderCode = $this->_order_prefix_bankpayment.substr($order->getIncrementId(),-10).$this->_order_suffix;
		} // if sonu
		elseif ($this->getConfig('grinet_kargo_genel_ayarlar/api_ayarlari/kapida_odeme_nakit') == $order->getPayment()->getMethod()) 
		{
			//$kargo_api_order_prefix = $this->getConfig($shipping_method.'/api/order_prefix_cod');
			$kargo_api_order_prefix = $this->getConfig('oto_ordersync/order_settings/order_prefix_cod');
			$orderCode = $kargo_api_order_prefix.substr($order->getIncrementId(),-10).$this->_order_suffix;
		} // if sonu
		elseif ($this->getConfig('grinet_kargo_genel_ayarlar/api_ayarlari/kapida_odeme_kredi_karti') == $order->getPayment()->getMethod()) 
		{
			//$kargo_api_order_prefix = $this->getConfig($shipping_method.'/api/order_prefix_cod_cc');
			$kargo_api_order_prefix = $this->getConfig('oto_ordersync/order_settings/order_prefix_cod_cc');
			$orderCode = $kargo_api_order_prefix.substr($order->getIncrementId(),-10).$this->_order_suffix;
		} // if sonu
		else 
		{
			$orderCode = $this->_order_prefix.substr($order->getIncrementId(),-10).$this->_order_suffix;
		} // else sonu

		$order_payment_state = 'PAID'; /// Sipariş Önerisi olarak gönder
		if (in_array($order->getPayment()->getMethod(), $this->_order_status_by_payment_method)) 
		{
			$order_payment_state = 'NOT_PAID'; /// Sipariş Önerisi olarak gönder
		} // if sonu

		$order_date = $this->getStoreBasedDate($order, "created_at", "Y-m-d");
		$order_time = $this->getStoreBasedDate($order, "created_at", "H:i:s");
		
		$bill = $order->getBillingAddress();

		// Adres kodu uret
		$customer_id = intval($order->getCustomerId());
		$code_prefix = $this->_customer_code_prefix;
		
		if ($customer_id < 1) 
		{
			$customer_id = $order->getIncrementId();
			$code_prefix = $this->_customer_code_prefix."G";
		} // if sonu

		$billAddress = join(" ",$bill->getStreet());

		$accountName = $bill->getFirstname()." ".$bill->getLastname();
		if ($bill->getCompany() != '') 
		{
			$accountName = $bill->getCompany();
		} // if sonu
		$accountName = $this->_customer_name_prefix.$accountName.$this->_customer_name_suffix;

		$gift_messages_combined = $this->getGiftMessagesCombined($order);
		
		$orderData = $order->getData();
		$orderData['order_code'] = $orderCode;
		$orderData['event'] = 'order.created';
		$orderData['payment'] = $order->getPayment()->getData();
		$orderData['billing_address'] = $order->getBillingAddress()->getData();

		if ($order->getShippingAddress()) 
		{
			$orderData['shipping_address'] = $order->getShippingAddress()->getData();
		} // if sonu
		else 
		{
			$orderData['shipping_address'] = [];
		} // else sonu
				
		if ($order->getCustomerId() > 0) 
		{
			$customer = $this->_objectManager->get('\Magento\Customer\Model\Customer')->load($order->getCustomerId());
			$orderData['customer'] = $customer->getData();
			unset($orderData['customer']['password_hash']);
		} // if sonu
		else 
		{
			$orderData['customer'] = [
										"email"			=>	 $orderData["customer_email"		],
										"prefix"		=>	 $orderData["customer_prefix"		],
										"firstname"		=>	 $orderData["customer_firstname"	],
										"middlename"	=>	 $orderData["customer_middlename"	],
										"lastname"		=>	 $orderData["customer_lastname"		],
										"suffix"		=>	 $orderData["customer_suffix"		],
										"taxvat"		=>	 $orderData["customer_taxvat"		],
										"gender"		=>	 $orderData["customer_gender"		],
										"dob"			=>	 $orderData["customer_dob"			],
										];
		} // else sonu
		
		
		$orderData['extension_attributes'] = $order->getExtensionAttributes();
		$orderData['gift_messages'] = $this->getGiftMessagesCombined($order);
		$orderData['items'] = [];

		foreach ($order->getAllVisibleItems() as $item) 
		{
			$orderData['items'][] = $item->getData();
		} // foreach sonu

		$orderData['oto']['date'] = date("Y-m-d H:i:s");

		$hash_str = $orderData['increment_id']."|".$orderData['oto']['date']."|".$this->api_access_secret;
		
		$hash = hash('sha512', $hash_str);

		$orderData['oto']['hash'] = $hash;
		$orderData['oto']['increment_id'] = $orderData['increment_id'];
		$orderData['oto']['account_name'] = $accountName;
		$orderData['oto']['order_payment_state'] = $order_payment_state;

		$payloadData = json_encode($orderData,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

		$curl_params = [
						'url'			=> $this->api_url,
						'data'			=> $payloadData,
						'type'			=> 'POST',
						'headers'		=> [],
						'debug'			=> false,
						'header_debug'	=> false,
						'debug_title'	=> 'ORDER POST',
					];

		if ($this->debug_order == true) 
		{
			print "\n==== ORDER SEND REQ -- PS L:".__LINE__."==================\n";
			print_r($curl_params);
			print "\n===== EOF PS ==========================================\n";
		} // if sonu

		$ret = $this->run_curl($curl_params);

		$crmOrderId		= 0;
		$crmOrderCode	= '--';
		$error_msg		= '';

		$log =  $this->_objectManager->create('Oto\OrderSync\Model\OrderSyncLogger');
		$log_arr = [
					'q_log_id'				=> null,
					'q_id'					=> $this->job->getId(),
					'q_target'				=> $this->job->getData('job_target'),
					'action_type'			=> 'ADD_ORDER',
					'order_id'				=> $this->job->getData('order_id'),
					'order_inc_id'			=> $this->job->getData('order_increment_id'),
					'data_sent'				=> $curl_params['data'],
					'data_response'			=> $ret['res'],
					'conn_stats'			=> var_export($ret['info'],true),
					'conn_success'			=> $ret['info']['http_code'] == 200 ?'1':'0',
					];

		$log->setData($log_arr)->save();
		
		try{
			$ret_arr = json_decode(trim(''.$ret['res']),true);

			if (@$ret_arr['success'] != true) 
			{
				$status = 'error';
				$error_msg = __("Error : %1", @$ret_arr['errors'][0]['title']." -- ".@$ret_arr['errors'][0]['detail']);
				goto RETURN_POINT;
			} // if sonu

			$status = 'success';
			$order_ret = @$ret_arr['data']['attributes'];
			$crmOrderId = $order_ret['id'] = @$ret_arr['data']['id'];
			$error_msg = "Order sent to Oto.";
			
			goto RETURN_POINT;

		}
		catch (Exception $e)
		{
			$status = 'error';
			$error_msg = "Order can not sent to Oto. Error : ".$e->getMessage();
			$returned_data = @$ret['res'];
		
			goto RETURN_POINT;

		}
		
		$status		= 'error';
		$error_msg	= (string) __("Order sent to Oto but confirmation information not received.");
		
		RETURN_POINT:

		if ($this->debug_order == true) 
		{
			print "\n==== ORDER SEND RES -- PS L:".__LINE__."==================\n";
			print_r($ret);
			print "\n===== EOF PS ==========================================\n";
		} // if sonu
		
		$ret = [
				'status'			=> @$status,
				'error_msg'			=> @$error_msg,
				'returned_data'		=> @$returned_data,
				'crmCustomerCode'	=> @$customerCode,
				'crmOrderId'		=> @$crmOrderId,
				'crmOrderCode'		=> @$crmOrderCode,
				];

		return $ret;

	} // eof func
	
}
