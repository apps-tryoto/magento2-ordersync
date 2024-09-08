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
class Disconnect extends Action
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

		$agree			= trim(''.@$post['agree']) == 'on' ? true:false;
		$delete_profile	= trim(''.@$post['delete_profile']) == 'on' ? true:false;
		
		if ($agree !== true)
		{
			$this->returnToLoginWithError(__('Error:')." ".__('You have to agree to disconnect.'));
			return;
		} // if sonu

		if ($delete_profile == true)
		{
			$integrationId = $this->_otoHelper->getConfig('oto_integration_id');
			$this->_otoHelper->deleteIntegration($integrationId);
		} // if sonu

		$this->_otoHelper->setConfig('oto_last_disconnect', $this->_otoHelper->getCurrentDateTime(), 'default', 0);
		$this->_otoHelper->setConfig('oto_hash_key', null, 'default', 0);
		$this->_otoHelper->setConfig('oto_webhook_url', null, 'default', 0);
		$this->_otoHelper->setConfig('oto_integration_name', null, 'default', 0);
		$this->_otoHelper->setConfig('oto_integration_id', null, 'default', 0);
		$this->_otoHelper->setConfig('oto_access_token', null, 'default', 0);
		$this->_otoHelper->setConfig('oto_access_secret', null, 'default', 0);

		$cacheCleanStatus = $this->_otoHelper->cacheClean(['config','config_integration','config_integration_api','config_webservice']);

		if (@$cacheCleanStatus['status'] == true) 
		{
			$this->returnToLoginWithSuccess(__('Your Store sucessfully disconnected from OTO. %1 caches cleaned.', @$cacheCleanStatus['cleaned_cache_types']));
		} // if sonu
		else 
		{
			$this->returnToLoginWithSuccess(__('Your Store sucessfully disconnected from OTO. <strong>Do not forget to Clean Cache</strong>.'));
		} // else sonu
		
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
