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

namespace Oto\OrderSync\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

#[\AllowDynamicProperties]
class UpgradeData implements UpgradeDataInterface
{

	/**
	 * Customer setup factory
	 *
	 * @var \Magento\Customer\Setup\CustomerSetupFactory
	 */
	private $customerSetupFactory;
	/**
	 * Init
	 *
	 * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
	 */
	public function __construct(\Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory)
	{
		$this->customerSetupFactory = $customerSetupFactory;
	}

	/**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup,ModuleContextInterface $context) 
	{

	}
}
