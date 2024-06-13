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

namespace Oto\OrderSync\Controller\Adminhtml\OrderSync;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

#[\AllowDynamicProperties]
class View extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Oto\OrderSync\Model\OrderSync $_orderSyncResource,
        \Oto\OrderSync\Model\OrderSyncLogger $_orderSyncLogger,
        \Oto\OrderSync\Helper\Data $_orderSyncHelper
    ) {
        $this->resultPageFactory	= $resultPageFactory;
        $this->_objectManager		= $_objectManager;
        $this->_orderSyncHelper	= $_orderSyncHelper;
        $this->_orderSyncResource	= $_orderSyncResource;
        $this->_orderSyncLogger	= $_orderSyncLogger;
        parent::__construct($context);
    }

	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Oto_OrderSync::Job_View');
	}

	// -------------------------------------------------------------------------------------------------------
	public function returnWithMessage($message, $type='success', $url = '') {

			if ($url == '') 
			{
				$url = $this->_redirect->getRefererUrl();
			} // if sonu

			if ($message != '') 
			{
				switch($type){
					case "success":		
									$this->_orderSyncHelper->_messageManager->addSuccess($message);
									break;
					case "error":		
									$this->_orderSyncHelper->_messageManager->addError($message);
									break;
					default :		break;
				}
				
			} // if sonu
			
			$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			
			$resultRedirect->setUrl($url);

			return $resultRedirect;	

	} // function sonu ---------------------------------------------------------------------------------------

    public function execute()
    {
		$job_id = $this->getRequest()->getParam('job_id');
		
		if ($job_id < 1) 
		{
			return $this->returnWithMessage(__('Job id is incorrect.'),'error','');	
		} // if sonu

		$job_data = $this->_orderSyncResource->load($job_id);
		
		if (!is_object($job_data) or $job_data->getId() < 1) 
		{
			return $this->returnWithMessage(__('Selected job is not loaded.'),'error','');	
		} // if sonu

		$job_log_data = $this->_orderSyncLogger->getCollection()->addFieldToSelect('*');
        $job_log_data->addFieldToFilter('job_id', array('eq' => $job_id));
        $job_log_data->setOrder('job_log_id', 'desc');
        $job_log_data->setPageSize(20);
        //$job_log_data->setCurrentPage(1);
		
		$this->_view->loadLayout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Order Sync Job Detail'));
        
		$resultPage->setActiveMenu('Oto::Reports::Order_Transactions');
		$block = $this->_view->getLayout()->createBlock('Oto\OrderSync\Block\Adminhtml\OrderSync\View');
		
		$block->setData('job_data',$job_data);
		$block->setData('job_log_data',$job_log_data);
		
		$this->_addContent($block);
        $this->_view->renderLayout();
	}

	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Oto_OrderSync::OrderSyncJob_View');
    }

}