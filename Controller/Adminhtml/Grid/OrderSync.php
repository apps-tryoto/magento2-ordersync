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

namespace Oto\OrderSync\Controller\Adminhtml\Grid;

#[\AllowDynamicProperties]
class OrderSync extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

	public function execute()
    {
        $this->_view->loadLayout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Order Sync Job List'));
        $resultPage->setActiveMenu('Oto_OrderSync::orderSync');
        $resultPage->addBreadcrumb(__('Oto'), __('Order Sync Job List'));
        $this->_addContent($this->_view->getLayout()->createBlock('Oto\OrderSync\Block\Adminhtml\OrderSync\Grid'));
        $this->_view->renderLayout();
    }
    
	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Oto_OrderSync::OrderSyncJob_List');
    }

}
