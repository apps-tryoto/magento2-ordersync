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
class OrderSyncCanceled extends \Oto\OrderSync\Helper\Data
{

	// -------------------------------------------------------------------------------------------------------
	public function syncCanceledOrders($params, $output) {

		if (!is_array($params)) 
		{
			$params = [];
		} // if sonu

		$this->output = $output;

		$order_inc_ids = $params['order'];
		$order_ids = '';

		$this->debug = $this->getConfig('oto_ordersync/debug/is_active');

		$this->_objectManager = $this->getObjectManager();

		$q =  $this->_objectManager->get('Oto\OrderSync\Model\OrderQueue');
		$order_jobs = $q->getCollection();
		$order_jobs->addFieldToFilter('job_type', 'cancel_order');

		$order_inc_ids		= explode(',',@$params['order']);
		$order_ids			= explode(',',@$params['order-id']);
		$this->force_order	= @$params['force-order'] == true ? true:false;
		$this->debug_order	= @$params['debug-order'] == true ? true:false;
		$this->dry_run		= @$params['dry-run'] == true ? true:false;

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
			$order_jobs->getSelect()->where(" job_type = 'cancel_order' and order_increment_id in('".join("','",$order_inc_ids)."')");
		} // if sonu
		elseif (is_array($order_ids) and count($order_ids) > 0) 
		{
			$order_jobs->getSelect()->where(" job_type = 'cancel_order' and order_id in('".join("','",$order_ids)."')");
		} // else sonu
		else 
		{
			$order_jobs->getSelect()->where(" job_type = 'cancel_order' and job_status in('WAITING','IN_PROGRESS') or ( job_status = 'ERROR' AND ( error_count < 100 or error_count IS NULL) ) ");
		} // else sonu

		if ($order_jobs->count() < 1) 
		{
			$this->output->writeln('<comment>'. $this->getCurrentDateTime() ." -- ". __('No order job to sync...').'</>')."\n";
			return;
		} // if sonu

		$this->_otoSessionId = $this->getOtoSession();
		echo "\n## OTO Session Key : ".substr($this->_otoSessionId,0,30)."...".substr($this->_otoSessionId,-30)."\n\n";

		if ($this->_otoSessionId === false) 
		{
			$this->output->writeln('<error>'. __("OTO oturumu açılamadı.").'</>')."\n";
			return;
		} // if sonu

		//// Order Loop ////////////////////////////////////////////////////////////////////////////

		foreach ($order_jobs as $job) 
		{
			$this->job = $job;

			$msg = $this->output->writeln('<comment>'.__("Job: %1 -- Order: #%2 -- Customer: %3 - Cust.Mail: %4 ",
						$job['q_id'],
						$job['order_increment_id'],
						$job['customer_name'],
						$job['customer_email']).'</>');

			echo " -- ".$msg;
			if ($this->debug == 1) 
			{
				$this->_objectManager->get('Psr\Log\LoggerInterface')->info('OTO :'.$msg);
			} // if sonu
			
			$job
				->setData('job_status','IN_PROGRESS')
				->setData('updated_at',$this->getCurrentDateTime())
				->save();

			$ret = $this->cancelOrderInTarget($job->getData('order_id'), $job);

			if ($ret['status'] == 'error') 
			{
				$job
					->setData('job_status','ERROR')
					->setData('error_count',$job->getData('error_count')+1)
					->setData('last_error_msg',@$ret['error_msg'])
					->setData('updated_at',$this->getCurrentDateTime())
					->save();

				$this->output->writeln('<error>'. __(' -- Sipariş Aktarılamadı : %1',@$ret['error_msg']).'</>')."\n";

			} // if sonu
			elseif ($ret['status'] == 'skipped') 
			{
				$job
					->setData('job_status','ERROR')
					//->setData('error_count',$job->getData('error_count')+1)
					->setData('last_error_msg',__('Bu sipariş zaten aktarılmış'))
					->setData('updated_at',$this->getCurrentDateTime())
					->save();

				$this->output->writeln('<error>'. __(' -- Sipariş Atlandı : %1',@$ret['error_msg']).'</>')."\n";

			} // if sonu
			elseif ($ret['status'] == 'success') 
			{
				$job
					->setData('job_status','SUCCESS')
					->setData('ret_account_id',@$ret['crmAccountId'])
					->setData('ret_order_id',@$ret['crmOrderId'])
					->setData('updated_at',$this->getCurrentDateTime())
					->save();

				if ($ret['error_msg'] != '') 
				{
					$ret['error_msg'] = ' ( '.$ret['error_msg'].' ) ';
				} // if sonu
				
				$this->output->writeln('<info>'. __(' -- Sipariş İptali Aktarıldı '.$ret['error_msg'].': ').$ret['crmOrderCode']." -- DB Id : ".@$ret['crmOrderId'].'</>')."\n";
				echo "\n";
			} // else sonu

		} // foreach sonu

		//// EOF Order Loop ////////////////////////////////////////////////////////////////////////////
		$this->endOrderSyncSession();

	} // function sonu ---------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------------------------------------
	public function cancelOrderInTarget($orderId, $jobData) {

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

		$orderData['event'] = 'order.canceled';
		$orderData['order_id'] = $order->getId();
		$orderData['order_code'] = $orderCode;
		$orderData['increment_id'] = $order->getIncrementId();
		$orderData['oto']['date'] = date("Y-m-d H:i:s");

		$hash_str = $orderData['increment_id']."|".$orderData['oto']['date']."|".$this->api_access_secret;
		
 		$hash = hash('sha512', $hash_str);

		$orderData['oto']['hash'] = $hash;
		$orderData['oto']['increment_id'] = $orderData['increment_id'];

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
					'action_type'			=> 'CANCEL_ORDER',
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

	} // function sonu ---------------------------------------------------------------------------------------

}
