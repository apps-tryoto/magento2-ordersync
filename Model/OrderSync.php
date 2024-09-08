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

namespace Oto\OrderSync\Model;

#[\AllowDynamicProperties]
class OrderSync extends \Magento\Framework\Model\AbstractModel{

    protected function _construct()
    {
        $this->_init(\Oto\OrderSync\Model\ResourceModel\OrderSync::class);
    }

	/*
		@function
	*/

	function save() {
	
		if (is_null($this->getData('created_at'))) 
		{
			$this->setData('created_at',$this->getCurrentDateTime());
		} // if sonu


		parent::save();

	} // eof func


}