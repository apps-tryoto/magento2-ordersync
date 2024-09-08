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

namespace Oto\OrderSync\Block\Adminhtml\Setup;

use Magento\Backend\Block\Template;

#[\AllowDynamicProperties]
class Index extends Template
{
	/*
		@function
	*/

	function __construct(
        \Magento\Backend\Block\Template\Context $context,
		\Oto\OrderSync\Helper\Data $_otoHelper	
	) 
	{
        parent::__construct($context);
		$this->_otoHelper = $_otoHelper;
	} // eof func
	
	/*
		@function
	*/
	function getTemplate() {

		if ($this->getIsStoreConnected() !== true) 
		{
			return 'setup/connect.phtml';
		} // if sonu
		else 
		{
			return 'setup/disconnect.phtml';
		} // else sonu

	}
	

	/*
		@function
	*/

	function getTitle() {
	
		if ($this->getIsStoreConnected() === true) 
		{
			return __('Disconnect From OTO');
		} // if sonu
		else 
		{
			return __('Connect To OTO');
		} // else sonu

	} // eof func

	/*
		@function
	*/
    protected function getIsStoreConnected()
    {
		$webhookUrl = trim(''.$this->_otoHelper->getConfigWoCache('oto_webhook_url'));
		$accessToken = trim(''.$this->_otoHelper->getConfigWoCache('oto_access_token'));

		if ($accessToken != '' and $webhookUrl != '') 
		{
			return true;
		} // if sonu

		return false;

	}
		
}
