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
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

#[\AllowDynamicProperties]
class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Connect To OTO'));
        return $resultPage;
    }

	/*
		@function
	*/

	function getTitle() {
		
		return (string) __('Denememe');

	} // eof func
	
	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Oto_OrderSync::Setup');
    }

}
