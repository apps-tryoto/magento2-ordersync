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

declare(strict_types=1);

namespace Oto\OrderSync\Block\Adminhtml\OrderSync;

#[\AllowDynamicProperties]
class View extends \Magento\Backend\Block\Template
{

	public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $_registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Oto\OrderSync\Model\OrderSync $_orderSyncResource,
        \Oto\OrderSync\Model\OrderSyncLogger $_orderSyncLogger,
        \Oto\OrderSync\Helper\Data $_orderSyncHelper,
        array $data = []
    )
	{
        $this->_objectManager		= $objectManager;
        $this->_registry			= $_registry;
        $this->_orderSyncResource	= $_orderSyncResource;
        $this->_orderSyncLogger	= $_orderSyncLogger;
        $this->_orderSyncHelper	= $_orderSyncHelper;

        $this->setTemplate('orderSync/view.phtml');
        $this->setName('orderSync.view');
		parent::__construct($context, $data);
    }

    protected function _toHtml() {
        return parent::_toHtml();
    }


}