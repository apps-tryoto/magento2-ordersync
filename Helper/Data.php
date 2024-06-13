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

#[\AllowDynamicProperties]
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	public		$_scopeConfig;
	public		$_objectManager;
	public		$_configData;
	public		$_configDataWoCache;
    public		$_date;
    public		$_currentDateTime;
    public		$_currentDate;
    public		$_currentUnixDate;
    public		$_orderRepository;
    public		$_magentoMetaData;
    public		$_configCollection;

	public		$_api_url;
	public		$_api_test_url;

	// -------------------------------------------------------------------------------------------------------
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
		\Magento\Framework\App\Config\Storage\WriterInterface $_scopeConfigWriter,	
		\Magento\Framework\ObjectManagerInterface $_objectManager,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $_date,
		\Magento\Sales\Api\OrderRepositoryInterface $_orderRepository,
		\Magento\Framework\Message\ManagerInterface $_messageManager,
		\Magento\Framework\App\ProductMetadataInterface $_magentoMetaData,
		\Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $_configCollection,
		\Magento\Customer\Api\CustomerRepositoryInterface $_customerRepository
    ) {
		
		$this->_scopeConfig			= $_scopeConfig;
		$this->_scopeConfigWriter	= $_scopeConfigWriter;
		$this->_objectManager		= $_objectManager;
        $this->_date				= $_date;
        $this->_orderRepository		= $_orderRepository;
        $this->_messageManager		= $_messageManager;
        $this->_magentoMetaData		= $_magentoMetaData;
        $this->_configCollection	= $_configCollection;
        $this->_customerRepository	= $_customerRepository;

		$this->getObjectManager();

		$this->api_url				= 'https://app.tryoto.com';
		$this->api_test_url			= 'https://qa.tryoto.com';

		parent::__construct($context);

	} // function sonu ---------------------------------------------------------------------------------------
	

	
	// -------------------------------------------------------------------------------------------------------
	public function getConfigWoCache($config_path, $website_id = 0, $store_id = 0)
	{
		
		//TODO: Add store and website filter

		if (!isset($this->_configDataWoCache[$config_path]))
		{
		
			$collection = $this->_configCollection->create();
			$collection->addFieldToFilter("path",['eq'=>$config_path]);

			if( $collection->count() > 0 )
			{
				$this->_configDataWoCache[$config_path] = $collection->getFirstItem()->getData()['value'];
			}

		} // if sonu

		return @$this->_configDataWoCache[$config_path];

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getConfig($config_path)
	{
		if (!isset($this->_configData[$config_path]))
		{
			if (!isset($this->_scopeConfig) or !is_object($this->_scopeConfig))
			{
				$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$this->_scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
			} // if sonu

			$this->_configData[$config_path] = $this->_scopeConfig->getValue($config_path,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		} // if sonu

		return $this->_configData[$config_path];
	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function setConfig($config_path = '', $value = '', $storeId = 0)
	{
		if ($config_path == '') 
		{
			return;
		} // if sonu
		
		$this->_scopeConfigWriter->save($config_path, $value, $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeId = 0);

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getMagentoVersion() {
		
		return [
				'edition' => $this->_magentoMetaData->getEdition(),
				'version' => $this->_magentoMetaData->getVersion(),
			];

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getObjectManager() {
	
		if (!$this->_objectManager) 
		{
			$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		} // if sonu

		return $this->_objectManager;
	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getCurrentDateTime() {

		if (!$this->_currentDateTime)
		{
			$this->_date = $this->getDateObject();
			$this->_currentDateTime = $this->_date->date()->format('Y-m-d H:i:s');
		} // if sonu

		return $this->_currentDateTime;

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getCurrentDate() {

		if (!$this->_currentDate)
		{
			$this->_date = $this->getDateObject();
			$this->_currentDate = $this->_date->date()->format('Y-m-d');
		} // if sonu

		return $this->_currentDate;

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getCurrentUnixDate() {

		if (!$this->_currentUnixDate)
		{
			$this->_date = $this->getDateObject();
			$this->_currentUnixDate = $this->_date->date()->format('U');
		} // if sonu

		return $this->_currentUnixDate;

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getStrToDate($str) {

		$this->_date = $this->getDateObject();
		$ret = $this->_date->date($str);
		return $ret;

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getDateObject() {

		if (!is_object($this->_date))
		{
			$this->getObjectManager();
			$this->_date = $this->_objectManager->get('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
		} // if sonu

		return $this->_date;

	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	public function getStoreBasedDate($object, $date_field = 'created_at', $format = 'Y-m-d H:i:s')
    {
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $object->getData($date_field));
        $timezone = $this->_scopeConfig->getValue('general/locale/timezone',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$object->getStoreId());

		if ($timezone) {
            $storeTime = new \DateTimeZone($timezone);
            $datetime->setTimezone($storeTime);
        }
        return $datetime->format($format); 
    }

	// -------------------------------------------------------------------------------------------------------
	public function getDbConnection() {

		if (!isset($this->_conn))
		{
			$this->_res		= $this->getObjectManager()->get('Magento\Framework\App\ResourceConnection');
			$this->_conn	= $this->_res->getConnection();
		} // if sonu

		return $this->_conn;

	} // function sonu ---------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------------------------------------
	public function run_curl($params = []) {

		$url			= @$params['url'];
		$data			= @$params['data'];
		$type			= @$params['type'];
		$headers		= !is_array(@$params['headers'])?[]:@$params['headers'];
		$debug			= @$params['debug'];
		$header_debug	= @$params['header_debug'];
		$debug_title	= @$params['debug_title'];

		if (trim(''.$url) == '') 
		{
			return false;
		} // if sonu

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		$headers_def = [
				'Content-Type: application/json',
				'Accept: application/json, application/octet-stream',
			];

		$headers = array_unique(array_merge($headers_def,$headers));

		if ($debug === true)
		{
			print "\n----- TYPE : $type -- URL : $url\n";
		} // if sonu

		if ($header_debug === true)
		{
			print "\n\e[93m==== RUN CURL HEADERS : $debug_title =========================================================\n\e[39m";
			print_r($headers);
			print "\n===== EOF HEADERS =============================================================\n";
		} // if sonu

		if ($debug === true and 
				(
					( is_array($data) and count($data) > 0 )	
					or
					( !is_array($data) and $data != '' )
				)	
			)
		{
			print "\n\e[93m===== RUN CURL DATA  : $debug_title ===============================================\e[39m\n";
			print_r($data);
			print "\n===== EOF DATA ====================================================\n";
			if (substr($data,0,1) == '{') 
			{
				print "\n\e[93m===== RUN CURL DATA ARR : $debug_title ===============================================\e[39m\n";
				print_r(json_decode($data,true));
				print "\n===== EOF DATA ====================================================\n";
			} // if sonu
			
		} // if sonu

		if ($type == 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} // if sonu

		if ($type == 'PUT')
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} // if sonu

		if ($type == 'PATCH')
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		} // if sonu

		if ($type == 'DELETE')
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		} // if sonu

		if (count($headers) > 0)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		} // if sonu

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT,120);

		$res	= curl_exec($ch);
		$info	= curl_getinfo($ch);

		if ($debug === true)
		{
			print "\n\e[93m===== CURL RESPONSE : $debug_title ===============================================\e[39m\n";
			print_r($res);
			print "\n===== EOF RESPONSE ====================================================\n";

			if (substr($res,0,1) == '{') 
			{
				print "\n\e[93m===== RUN CURL DATA ARR : $debug_title ===============================================\e[39m\n";
				print_r(json_decode($res,true));
				print "\n===== EOF DATA ====================================================\n";
			} // if sonu
		} // if sonu

		return array('res' => $res, 'info' => $info);


	} // function sonu ---------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------------------------------------
	function createNewIntegration($name = '', $email = '', $endpoint = '') {
	
		if ($name == '') 
		{
			return ['status' => false, 'message' => (string) __('Integration name is empty.')];
		} // if sonu
		
		if ($email == '') 
		{
			return ['status' => false, 'message' => (string) __('Integration email is empty.')];
		} // if sonu
		
		// Code to check whether the Integration is already present or not
		$intDataCurrent = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($name,'name')->getData();

		if(empty($intDataCurrent)){

			$integrationData = array(
				'name' => $name,
				'email' => $email,
				'status' => '1',
				'endpoint' => $endpoint,
				'setup_type' => '0'
			);
			
			try{
			
				// Code to create Integration
				$integrationFactory = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create();
				$integration = $integrationFactory->setData($integrationData);
				$integration->save();
				$integrationId = $integration->getId();
				
				$consumerName = $name . $integrationId;

				// Code to create consumer
				$oauthService = $this->_objectManager->get('Magento\Integration\Model\OauthService');
				$consumer = $oauthService->createConsumer(['name' => $consumerName]);
				$consumerId = $consumer->getId();
				$integration->setConsumerId($consumer->getId());
				$integration->save();

				$consumerId = $consumer->getId();
				$consumerKey = $consumer->getData('key');
				$consumerSecret = $consumer->getData('secret');

				// Code to grant permission
				$authrizeService = $this->_objectManager->get('Magento\Integration\Model\AuthorizationService');
				$authrizeService->grantAllPermissions($integrationId);

				// Code to Activate and Authorize
				$token = $this->_objectManager->get('Magento\Integration\Model\Oauth\Token');
				$uri = $token->createVerifierToken($consumerId);
				$token->setType('access');
				$token->save();

				$accessToken = $token->getData('token');
				$accessSecret = $token->getData('secret');

				$intData = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($name,'name')->getData();

				return [
						'status' => true, 
						'message' => (string) __('Integration created successfully with name %1.', $name),
						'intData' => $intData,
						'integrationId' => $integrationId,
						'consumerId' => $consumerId,
						'consumerKey' => $consumerKey,
						'consumerSecret' => $consumerSecret,
						'accessToken' => $accessToken,
						'accessSecret' => $accessSecret,
					];
			}
			catch(Exception $e)
			{
				return ['status' => false, 'message' => (string) __($e->getMessage())];
			}
		}

	} // function sonu ---------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------------------------------------
	function deleteIntegration($integrationId = 0) {
	
		if ($integrationId == 0) 
		{
			return ['status' => false, 'message' => (string) __('Integration id is empty or zero.')];
		} // if sonu

		// Code to check whether the Integration is already present or not
		$integration = $this->_objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($integrationId);

		if(empty($integration)){

			try{
				$integration->delete();
				return ['status' => true, 'message' => (string) __('Integration deleted.')];
			}
			catch(Exception $e)
			{
				return ['status' => false, 'message' => (string) __($e->getMessage())];
			}
		}

	} // function sonu ---------------------------------------------------------------------------------------
	
	/*
		@function
	*/
    public function getDefaultStoreName()
    {
		return trim(''.$this->getConfig('general/store_information/name'));

	}
	
	/*
		@function
	*/
    public function getBearerSetting()
    {
		return trim(''.$this->getConfig('oauth/consumer/enable_integration_as_bearer'));

	}
}
