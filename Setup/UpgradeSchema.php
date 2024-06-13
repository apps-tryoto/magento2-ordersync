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

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

#[\AllowDynamicProperties]
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        $installer = $setup;

        $installer->startSetup();
		$connection = $installer->getConnection();		

        $this->type_int   = Table::TYPE_INTEGER;
        $this->type_text  = Table::TYPE_TEXT;
        $this->type_dec   = Table::TYPE_DECIMAL;
        $this->type_dtime = Table::TYPE_DATETIME;

		$type_int   = Table::TYPE_INTEGER;
        $type_text  = Table::TYPE_TEXT;
        $type_dec   = Table::TYPE_DECIMAL;
        $type_dtime = Table::TYPE_DATETIME;

		if (version_compare($context->getVersion(), "1.0.0", "=")) {

			$tableName = $installer->getTable('directory_country_region') ;if ($connection->tableColumnExists($tableName, 'oto_code') === false) {$connection->addColumn($tableName,'oto_code'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Region Code'	]);}
			$tableName = $installer->getTable('directory_region_city'   ) ;if ($connection->tableColumnExists($tableName, 'oto_code') === false) {$connection->addColumn($tableName,'oto_code'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Region Code'	]);}
			$tableName = $installer->getTable('directory_city_township' ) ;if ($connection->tableColumnExists($tableName, 'oto_code') === false) {$connection->addColumn($tableName,'oto_code'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Region Code'	]);}
        }

		$tableName = $installer->getTable('customer_address_entity' ) ;if ($connection->tableColumnExists($tableName, 'oto_account_id') === false) {$connection->addColumn($tableName,'oto_account_id'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Account ID'	]);}
		$tableName = $installer->getTable('customer_address_entity' ) ;if ($connection->tableColumnExists($tableName, 'oto_account_code') === false) {$connection->addColumn($tableName,'oto_account_code'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Account Code']);}

		$tableName = $installer->getTable('customer_entity' ) ;if ($connection->tableColumnExists($tableName, 'oto_account_id') === false) {$connection->addColumn($tableName,'oto_account_id'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Account ID'	]);}
		$tableName = $installer->getTable('customer_entity' ) ;if ($connection->tableColumnExists($tableName, 'oto_account_code') === false) {$connection->addColumn($tableName,'oto_account_code'	,['type' => $type_text	,'length' => 64,	'nullable' => true,'comment' => 'Oto Account Code']);}


		/// Tabloya ek alanlar 
		$tableName = $setup->getTable('oto_order_jobs_log');
		if ($connection->tableColumnExists($tableName, 'action_type') === false)			{$connection->addColumn($tableName,'action_type'	,		['type' => $this->type_text	,'length' => 48,	'nullable' => true,'comment' => 'Order ID', 'after' => 'q_target']);}
		if ($connection->tableColumnExists($tableName, 'order_id') === false)				{$connection->addColumn($tableName,'order_id'		,		['type' => $this->type_text	,'length' => 32,	'nullable' => true,'comment' => 'Order ID', 'after' => 'action_type']);}
		if ($connection->tableColumnExists($tableName, 'order_inc_id') === false)			{$connection->addColumn($tableName,'order_inc_id'	,		['type' => $this->type_text	,'length' => 48,	'nullable' => true,'comment' => 'Order Increment ID', 'after' => 'order_id']);}

		/// Tabloya ek alanlar 
		$tableName = $setup->getTable('oto_order_jobs');
		if ($connection->tableColumnExists($tableName, 'ret_order_completed') === false)	{$connection->addColumn($tableName,'ret_order_completed'	,['type' => $this->type_text	,'length' => 1,		'nullable' => true, 'default' => 'N', 'comment' => 'Sipariş karşı yazılımda tamamlandı veya faturası kesildi mi ?', 'after' => 'ret_account_id']);}
		if ($connection->tableColumnExists($tableName, 'ret_invoice_number') === false)		{$connection->addColumn($tableName,'ret_invoice_number'		,['type' => $this->type_text	,'length' => 96,	'nullable' => true, 'default' => null, 'comment' => 'Karşı yazılımda kesilen fatura numarası', 'after' => 'ret_order_completed']);}
		if ($connection->tableColumnExists($tableName, 'ret_invoice_date') === false)		{$connection->addColumn($tableName,'ret_invoice_date'		,['type' => $this->type_dtime,	'length' => null,	'nullable' => true, 'default' => null, 'comment' => 'Karşı yazılımda kesilen fatura tarihi', 'after' => 'ret_invoice_number']);}

    }
}
