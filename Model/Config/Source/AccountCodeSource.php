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

namespace Oto\OrderSync\Model\Config\Source;

use Magento\Payment\Model\Config\Source\Allmethods;

/**
 * Class Payment
 */
class AccountCodeSource implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
    	$options = array(
						"billing_address_id"	=> __("Invoice Address Id"),
						"customer_id"			=> __("Customer Id"),
						"both"					=> __("Customer Id + Invoice Address Id"),
						);
		return $options;
    }
}
