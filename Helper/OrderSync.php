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
class OrderSync extends \Oto\OrderSync\Helper\Data
{
	/*
		@function
	*/

	public function __construct() {

		$this->db = $this->getDbConnection();

		$this->_order_prefix						= $this->getConfig('oto_ordersync/order_settings/order_prefix');
		$this->_order_prefix_bankpayment			= $this->getConfig('oto_ordersync/order_settings/order_prefix_bankpayment');
		$this->_order_prefix_zero_total				= $this->getConfig('oto_ordersync/order_settings/order_prefix_zero_total');
		$this->_order_prefix_bankpayment_method		= explode(",",trim(''.$this->getConfig('oto_ordersync/order_settings/order_prefix_bankpayment_method'))); 

		$this->_order_suffix						= $this->getConfig('oto_ordersync/order_settings/order_suffix');
		$this->_order_desc_suffix					= $this->getConfig('oto_ordersync/order_settings/order_desc_suffix');

		$this->_order_item_use_one_item_enabled		= $this->getConfig('oto_ordersync/order_settings_one_item/enabled');

		$this->_order_start_from_order_id			= intval($this->getConfig('oto_ordersync/order_settings/start_from_order_id'));

		$this->_customer_code_prefix				= $this->getConfig('oto_ordersync/account_settings/account_code_prefix');
		$this->_customer_code_suffix				= $this->getConfig('oto_ordersync/account_settings/account_code_suffix');
		$this->_customer_name_prefix				= $this->getConfig('oto_ordersync/account_settings/account_name_prefix');
		$this->_customer_name_suffix				= $this->getConfig('oto_ordersync/account_settings/account_name_suffix');
		$this->_customer_accounting_code			= $this->getConfig('oto_ordersync/account_settings/account_code');
		$this->_account_code_source					= $this->getConfig('oto_ordersync/account_settings/account_code_source');
		$this->_account_payment_type				= $this->getConfig('oto_ordersync/account_settings/account_payment_type');
		$this->_account_ord_day						= $this->getConfig('oto_ordersync/account_settings/account_ord_day');

		$this->_send_as_tax_included				= $this->getConfig('oto_ordersync/order_settings/send_as_tax_included');

		$this->delete_is_enabled					= false; /// Sistem genelinde karşıdan yazılımdan kayıt silmeyi açmak için true
		$this->_order_status_by_payment_method		= explode(",",trim(''.$this->getConfig('oto_ordersync/order_settings/order_status_by_payment_method'))); 

		$this->fs									= $this->_objectManager->create('\Magento\Framework\Filesystem');
		$this->pubPath								= $this->fs->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::PUB)->getAbsolutePath();
		$this->urlInterface							= $this->_objectManager->create('\Magento\Framework\UrlInterface');

		$this->_storeManager						= $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface');
		$this->_transportBuilder					= $this->_objectManager->create('\Magento\Framework\Mail\Template\TransportBuilder');
		$this->_inlineTranslation					= $this->_objectManager->create('\Magento\Framework\Translate\Inline\StateInterface');

	} // eof func
	
	
	/*
		@function
	*/

	public function getApiCredentials() {

		$this->api_url			= 'https://oto-webhook-listener-2yycsthcma-ew.a.run.app/salesChannel/magento/VGdYYTNUN2k5dUdCaEQ1MUZPVzJCZz09';
		$this->api_hash_key		= trim(''.$this->getConfig('oto_ordersync/connection_settings/api_hash_key'));

	} // eof func


	/*
		@function
	*/

	function getGiftMessagesCombined($order) {
	
		$ret = false;

		$this->_gift_messages_enabled				= $this->getConfig('oto_ordersync/order_gift_messages/gift_messages_enabled'	);
		$this->_gift_messages_enabled_product		= $this->getConfig('oto_ordersync/order_gift_messages/gift_messages_enabled_product'	);
		$this->_gift_message_field_on_ordersync		= trim(''.$this->getConfig('oto_ordersync/order_gift_messages/gift_message_field_on_ordersync'	));

		if ($this->_gift_messages_enabled != 1) 
		{
			return $ret;
		} // if sonu

		if (!isset($this->_giftModel) or !is_object($this->_giftModel)) 
		{
			$this->_giftModel = $this->_objectManager->get('\Magento\GiftMessage\Model\Message');
		} // if sonu
		
		if (isset($this->_giftModel) and is_object($this->_giftModel)) 
		{
			if ($order->getGiftMessageId() > 0) 
			{
				$orderGiftMessage = $this->_giftModel->load($order->getGiftMessageId());

				if (is_object($orderGiftMessage) and $orderGiftMessage->getId() > 0) 
				{
					$ret.= "ORDER GIFT MESSAGE ------------------------------------\n";
					$ret.= "FROM : ".$orderGiftMessage->getSender()."\n";
					$ret.= "TO : ".$orderGiftMessage->getRecipient()."\n";
					$ret.= "MESSGE : ".$orderGiftMessage->getMessage()."\n";
					$ret.= "\n---------------\n\n";
				} // if sonu
				
			} // if sonu
			
			if ($this->_gift_messages_enabled_product == 1) 
			{
				$orderItems = $order->getAllVisibleItems();
				foreach ($orderItems as $item) 
				{

					if ($item->getGiftMessageId() < 1) 
					{
						continue;
					} // if sonu
					
					$prodGiftMessage = $this->_giftModel->load($item->getGiftMessageId());

					if (is_object($prodGiftMessage) and $prodGiftMessage->getId() > 0) 
					{
						$ret.= "GIFT MESSAGE ------------------------------------\n";
						$ret.= "PRODUCT CODE/SKU : ".$item->getSku()." // PRODUCT NAME : ".$item->getName()."\n";
						$ret.= "FROM : ".$prodGiftMessage->getSender()."\n";
						$ret.= "TO : ".$prodGiftMessage->getRecipient()."\n";
						$ret.= "MESSAGE : ".$prodGiftMessage->getMessage()."\n";
						$ret.= "\n---------------\n\n";
					} // if sonu
				}
			} // if sonu
			
		} // if sonu

		return $ret;
	
	} // eof func
	
	
	/*
		@function
	*/

	public function checkBackorderStatus($prod_id, $product) {
	
		if (intval($prod_id) < 1) 
		{
			return false;
		} // if sonu
		
		if (!is_object($product) or $product->getId() < 1) 
		{
			return false;
		} // if sonu
		
		if ($this->_backorder_orders_at_date != '1') 
		{
			return false;
		} // if sonu
					
		if ($this->_backorder_date_field == '') 
		{
			return false;
		} // if sonu

		$this->getDbConn();

		$sql = "SELECT product_id, backorders FROM " . $this->res->getTableName('cataloginventory_stock_item')." WHERE product_id = '".$prod_id."'";
		$stockItem = $this->db->query($sql)->fetch();

		if ($stockItem['backorders'] < 1 ) 
		{
			return false;
		} // if sonu

		$backorder_date = date("Ymd",strtotime(trim(''.$product->getData($this->_backorder_date_field))));

		if ($backorder_date > date("Ymd")) 
		{
			return true;
		} // if sonu
				
		return false;

	} // eof func
	
	/*
		@function
	*/

	public function getBackorderDate($product) {
		
		if (!is_object($product) or $product->getId() < 1) 
		{
			return false;
		} // if sonu
		
		if ($this->_backorder_date_field == '') 
		{
			return false;
		} // if sonu

		$this->getDbConn();

		$sql = "SELECT product_id, backorders FROM " . $this->res->getTableName('cataloginventory_stock_item')." WHERE product_id = '".$product->getId()."'";
		$stockItem = $this->db->query($sql)->fetch();

		if ($stockItem['backorders'] < 1 ) 
		{
			return false;
		} // if sonu

		$backorder_date = date("Ymd",strtotime(trim(''.$product->getData($this->_backorder_date_field))));

		return $backorder_date;

	} // eof func
	

	/*
		@function
	*/

	public function getAccountCode($order) {

		$this->_targetAccountId = null;
		$this->_targetAccountCode = null;

		if ($this->_account_code_source == 'customer_id' and $order->getCustomerId() > 0) 
		{
			$customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($order->getCustomerId());
			if (is_object($customer) and $customer->getId() > 0 and intval(trim(''.$customer->getData('oto_ordersync_account_id'))) > 1) 
			{
				$this->_targetAccountId = trim(''.$customer->getData('oto_ordersync_account_id'));
				$this->_targetAccountCode = trim(''.$customer->getData('oto_ordersync_account_code'));
				return;
			} // if sonu
			
		} // if sonu

		if ($this->_account_code_source == 'billing_address_id' and $order->getCustomerId() > 0) 
		{
			$addressId = $order->getBillingAddress()->getCustomerAddressId();

			if ($addressId > 0) 
			{
				$sql = "SELECT * FROM customer_address_entity WHERE entity_id = '".$addressId."'";
				$addressData = $this->db->query($sql)->fetch();

				if (@$addressData['oto_ordersync_account_id'] > 1) 
				{
					$this->_targetAccountId		= trim(''.@$addressData['oto_ordersync_account_id']);
					$this->_targetAccountCode	= trim(''.@$addressData['oto_ordersync_account_code']);
					return;
				} // if sonu
			} // if sonu

		} // if sonu

		$cari_ret = $this->createNewAccountInTarget($order);		

		if ($cari_ret['status'] == true) 
		{
			$this->_targetAccountId = @$cari_ret['crmCustomerId'];
			$this->_targetAccountCode = @$cari_ret['crmCustomerCode'];

			if ($this->_account_code_source == 'customer_id') 
			{
				if ($order->getCustomerId() > 0 and is_object($customer) and $customer->getId() > 0) 
				{
					$customer
						->setData('oto_ordersync_account_id', $this->_targetAccountId)
						->setData('oto_ordersync_account_code', $this->_targetAccountCode)
						->save();
				} // if sonu
			} // if sonu

			return true;
		
		} // if sonu

		return false;		

	} // eof func

	/*
		@function
	*/

	public function getBillingAndShippingIsSame($order) {

		if ($order->getIsVirtual()) 
		{
			return false;
		} // if sonu
		
		$compare_fields = [
							'customer_address_id',
							'region_id',
							'region',
							'postcode',
							'lastname',
							'street',
							'city',
							'email',
							'telephone',
							'country_id',
							'firstname',
							'middlename',
							'company',
							'township',
							'township_id',
							'tax_office',
							'tax_number',
						];
		
		$billAddressData = $order->getBillingAddress()->getData();
		$shipAddressData = $order->getShippingAddress()->getData();

		foreach ($compare_fields as $field) 
		{
			if ( trim(''.@$billAddressData[$field]) != trim(''.@$shipAddressData[$field]) ) 
			{
				return false;
			} // if sonu
		} // foreach sonu	
		
		return true;

	} // eof func

	/*
		@function
	*/

	public function getRegionCodeFromId($regionId) {

		$this->getDbConn();

		//Select Data from table
		$sql = "SELECT code FROM " . $this->res->getTableName('directory_country_region')." WHERE region_id = '".$regionId."'";
		$regionData = $this->db->query($sql)->fetch();

		if (@$regionData['code'] != '') 
		{
			return @$regionData['code'];
		} // if sonu
		
		return false;

	} // eof func
	
	/*
		@function
	*/

	public function getAttributeIdByCode($attribute_code) {

		$this->getDbConn();

		//Select Data from table
		$sql = "SELECT attribute_id FROM " . $this->res->getTableName('eav_attribute')." WHERE attribute_code = '".$attribute_code."'";
		$attribute = $this->db->query($sql)->fetch();

		if (@$attribute['attribute_id'] != '') 
		{
			return @$attribute['attribute_id'];
		} // if sonu
		
		return false;

	} // eof func
	
	/*
		@function
	*/

	public function getCityCodeFromId($cityId) {

		$this->getDbConn();

		//Select Data from table
		$sql = "SELECT * FROM " . $this->res->getTableName('directory_region_city')." WHERE city_id = '".$cityId."'";
		$cityData = $this->db->query($sql)->fetch();

		if (@$cityData['oto_ordersync_code'] != '') 
		{
			return @$cityData['oto_ordersync_code'];
		} // if sonu
		
		return false;

	} // eof func
	
	/*
		@function
	*/

	public function getDbConn() {

		if (!@$this->db) 
		{
			$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
			$this->res		= $this->objectManager->get('Magento\Framework\App\ResourceConnection');
			$this->db		= $this->res->getConnection();
		} // if sonu

	} // eof func
	
	/*
		@function
	*/

	function getOtoOrderDetail($otoOrderId = 0) {
	
		if ($otoOrderId == 0) 
		{
			return false;
		} // if sonu

		if (strlen(@$this->_otoSessionId) < 10) 
		{
			$this->_otoSessionId = $this->getOtoSession();
		} // if sonu
		
		$curl_params = [
						'url'			=> $this->api_url.$this->api_vers.''.$this->api_company_id.'/sales_invoices/'.$otoOrderId."?include=active_e_document",
						'data'			=> '',
						'type'			=> 'GET',
						'headers'		=> ['Authorization: Bearer '. $this->_otoSessionId],
						'debug'			=> false,
						'header_debug'	=> false,
						'debug_title'	=> 'OTO ORDER DETAIL GET',
					];

		if ($this->debug_order == true) 
		{
			print "\n==== ORDER SEND REQ -- PS L:".__LINE__."==================\n";
			print_r($curl_params);
			print "\n===== EOF PS ==========================================\n";
		} // if sonu

		$ret = $this->run_curl($curl_params);
		$ret_arr = json_decode(trim(''.$ret['res']),true);

		return $ret_arr;
	
	} // eof func
	

}
