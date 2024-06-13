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

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

#[\AllowDynamicProperties]
class InstallData implements InstallDataInterface
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
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {

		$customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
		$entityTypeId = $customerSetup->getEntityTypeId(\Magento\Customer\Model\Customer::ENTITY);
		$customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, "oto_account_code");

		$customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, "oto_account_code",  array(
			"type"		=> "varchar",
			"backend"	=> "",
			"label"		=> "Oto Cari Kodu",
			"input"		=> "text",
			"source"	=> "",
			"visible"	=> true,
			"required"	=> false,
			"default"	=> "",
			"frontend"	=> "",
			"unique"	=> false,
			"note"		=> ""
		));

		$oto_account_code   = $customerSetup->getAttribute(\Magento\Customer\Model\Customer::ENTITY, "oto_account_code");

		$oto_account_code = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'oto_account_code');
//		$used_in_forms[]="adminhtml_customer";
//		$used_in_forms[]="checkout_register";
//		$used_in_forms[]="customer_account_create";
//		$used_in_forms[]="customer_account_edit";
//		$used_in_forms[]="adminhtml_checkout";
		$oto_account_code
			//->setData("used_in_forms", $used_in_forms)
			->setData("is_used_for_customer_segment", true)
			->setData("is_system", 0)
			->setData("is_user_defined", 1)
			->setData("is_visible", 1)
			->setData("is_used_in_grid", 1)
			->setData("is_visible_in_grid", 1)
			->setData("is_filterable_in_grid", 1)
			->setData("is_searchable_in_grid", 1)
			->setData("sort_order", 100);

		$oto_account_code->save();
	
	}
}
