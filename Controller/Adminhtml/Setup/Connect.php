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

namespace Oto\OrderSync\Controller\Adminhtml\Setup;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;

#[\AllowDynamicProperties]
class Connect extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Oto\OrderSync\Helper\Data $_otoHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_otoHelper = $_otoHelper;
        parent::__construct($context);
    }

    public function execute()
    {

        $post = $this->getRequest()->getPostValue();

		$storeName	= trim(''.@$post['oto_store_name']);
		$userName	= trim(''.@$post['oto_username']);
		$password	= trim(''.@$post['oto_password']);
		$totp_key	= trim(''.@$post['totp_key']);
		
		if (strlen($storeName) < 1)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('You can not leave empty Store Name field.'));
			return;
		} // if sonu

		if (strlen($userName) < 5)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('Username is too short.'));
			return;
		} // if sonu

		if (strlen($password) < 2)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('Password is too short.'));
			return;
		} // if sonu

		$this->_otoHelper->setConfig('oto_last_login_attempt', $this->_otoHelper->getCurrentDateTime(), 'default', 0);

		$api_url = $this->_otoHelper->api_url.'/rest/v3/login'
			
		$payloadArr = [
						'email'		=> $userName,
						"password"	=> $password,
					];

		if ($totp_key != '') 
		{
			$api_url = $this->_otoHelper->api_url.'/rest/v3/loginWithTOTP';
			$payloadArr['activationCode'] = $totp_key;
		} // if sonu
		
		$curl_params = [
						'url'			=> $api_url,
						'data'			=> json_encode($payloadArr),
						'type'			=> 'POST',
					];

		$ret = $this->_otoHelper->run_curl($curl_params);

		try{
			$ret_arr = json_decode(trim(''.$ret['res']),true);

			if (@$ret_arr['success'] != true)
			{
				$this->returnToLoginWithError(__('Error:')." ".__(@$ret_arr['msg']));
				return;
			} // if sonu

			if (@$ret_arr['success'] === true)
			{

			} // if sonu

		}
		catch (Exception $e)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('An unknown error occured.'));
			return;
		}

		if (strlen(@$ret_arr['accessToken']) < 32)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('We can not authenticate to OTO'));
			return;
		} // if sonu

		$this->accessToken = @$ret_arr['accessToken'];

		$randomHash = md5(uniqid('',true).".".date("YmdHis"));

		/* Make New Integration And Send Key To Oto */

		$integrationName	= 'OtoIntegration-'.date("Y.m.d").'-'.substr(str_shuffle("abcdef0123456789"),0,4);
		$integrationEmail	= 'integration.'.date("Y.m.d").'@tryoto.com';

		$intData = $this->_otoHelper->createNewIntegration($integrationName, $integrationEmail, $endpoint = '');

		$accessToken	= @$intData['accessToken'];
		$accessSecret	= @$intData['accessSecret'];
		
		$consumerKey	= @$intData['consumerKey'];
		$consumerSecret = @$intData['consumerSecret'];

		if (@$intData['status'] !== true) 
		{
			$this->returnToLoginWithError(__('Error:')." ".__('Can not create Integration record and keys in Magento. Reason: %1', @$intData['message']));
			return;
		} // if sonu

		$magentoVersion = $this->_otoHelper->getMagentoVersion();

		$payloadData = json_encode([
							//'ecomType' => 'Magento_'.@$magentoVersion['edition']."-".@$magentoVersion['version'],
							'ecomType' => 'magento2_2',
							'queryParams' => [
								'hostname'	=> $this->_otoHelper->getConfig('web/secure/base_url'),
								'token'		=> $accessToken,
								'secret'	=> $accessSecret,
								'storename' => $storeName,
								'codValue'	=> $this->_otoHelper->getConfig('oto_ordersync/order_settings/order_prefix_cod_method'),
							],
						]);

		$curl_params = [
						'url'			=> $this->_otoHelper->api_url.'/web/v1/salesChannel/authorize',
						'data'			=> $payloadData,
						'type'			=> 'POST',
						'headers'		=> ['Authorization: Bearer '.$this->accessToken],
						'debug'			=> false,
						'header_debug'	=> false,
					];

		$ret = $this->_otoHelper->run_curl($curl_params);

		try{
			$ret_arr = json_decode(trim(''.$ret['res']),true);

			$webhookUrl = trim(''.@$ret_arr['webhookUrl']);

			if ($webhookUrl != '' and strlen($webhookUrl) > 20)
			{
				$this->_otoHelper->setConfig('oto_last_sales_channel_authorize', $this->_otoHelper->getCurrentDateTime(), 'default', 0);
				$this->_otoHelper->setConfig('oto_hash_key', $randomHash, 'default', 0);
				$this->_otoHelper->setConfig('oto_webhook_url', $webhookUrl, 'default', 0);
				$this->_otoHelper->setConfig('oto_integration_name', $integrationName, 'default', 0);
				$this->_otoHelper->setConfig('oto_integration_id', @$intData['integrationId'], 'default', 0);
				$this->_otoHelper->setConfig('oto_access_token', @$intData['accessToken'], 'default', 0);
				$this->_otoHelper->setConfig('oto_access_secret', @$intData['accessSecret'], 'default', 0);

				$cacheCleanStatus = $this->_otoHelper->cacheClean(['config','config_integration','config_integration_api','config_webservice']);
	
				if (@$cacheCleanStatus['status'] == true) 
				{
					$this->returnToLoginWithSuccess(__('Your Store sucessfully connected to OTO.'));
				} // if sonu
				else 
				{
					$this->returnToLoginWithSuccess(__('Your Store sucessfully connected to OTO. <strong>Do not forget to Clean Cache</strong>.'));
				} // else sonu

				return;
			} // if sonu
			else 
			{
				$this->returnToLoginWithError(__('Error:')." ".__(@$ret_arr['message']));
				return;
			} // else sonu

		}
		catch (Exception $e)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('An unknown error occured.'));
			return;
		}

		$this->_redirect('*/*/index');

	}

	/*
		@function
	*/

	function returnToLoginWithError($errorMessage = '') {

		if ($errorMessage != '')
		{
			$this->getMessageManager()->addErrorMessage($errorMessage);
		} // if sonu

		return $this->_redirect('oto/setup');

	} // eof func

	/*
		@function
	*/

	function returnToLoginWithSuccess($message = '') {

		if ($message != '')
		{
			$this->getMessageManager()->addSuccessMessage($message);
		} // if sonu

		return $this->_redirect('oto/setup');

	} // eof func


	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Oto_OrderSync::Setup');
    }
}
