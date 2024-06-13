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

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

#[\AllowDynamicProperties]
class InstallSchema implements InstallSchemaInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    private $eavSetup;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory, EavSetup $eavSetup)
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavSetup = $eavSetup;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $type_int   = Table::TYPE_INTEGER;
        $type_text  = Table::TYPE_TEXT;
        $type_dec   = Table::TYPE_DECIMAL;
        $type_dtime = Table::TYPE_DATETIME;

        /**
         * Create table 'oto_order_jobs'
         */

		$connection = $installer->getConnection();

        $table = $connection
            ->newTable($installer->getTable('oto_order_jobs'))
			->addColumn('job_id'                 , $type_int,		10,		['nullable' => false,'unsigned' => true,'auto_increment' => true,'primary' => true],'Job ID')
			->addColumn('job_type'             , $type_text,	16,		['nullable' => false],	'Job Type : new_order, cancel_order, partial_cancel_order')
			->addColumn('job_target'           , $type_text,	16,		['nullable' => false],	'Job Targeti : logo, nebim, netsis, uyum, mikro... ')
			->addColumn('order_id'             , $type_int,		10,		['nullable' => false,'unsigned' => true],'Sipariş ID')
			->addColumn('order_increment_id'   , $type_text,	24,		['nullable' => false],	'Sipariş NO')
			->addColumn('customer_id'          , $type_int,		10,		['nullable' => true,'unsigned' => true],'Müşteri ID')
			->addColumn('customer_name'        , $type_text,	64,		['nullable' => true],	'Müşteri Adı')
			->addColumn('customer_email'       , $type_text,	64,		['nullable' => true],	'Müşteri E-mail')
			->addColumn('created_at'           , $type_dtime,	null,	['nullable' => false],	'Oluşturulma tarihi')
			->addColumn('updated_at'           , $type_dtime,	null,	['nullable' => true],	'Güncelleme tarihi')
			->addColumn('job_status'           , $type_text,	24,		['nullable' => true],	'İşlem Durumu')
			->addColumn('error_count'          , $type_int,		4,		['nullable' => true,'unsigned' => true],	'Error Count')
			->addColumn('ret_order_id'         , $type_text,	24,		['nullable' => true,'unsigned' => true],	'Kaydedilen Sipariş No')
			->addColumn('ret_account_id'       , $type_text,	24,		['nullable' => true,'unsigned' => true],	'Kaydedilen Account Id')
			->addColumn('ret_order_completed'  , $type_text,	1,		['nullable' => true,'default' => "N"],	'Sipariş karşı yazılımda tamamlandı veya faturası kesildi mi ?')
			->addColumn('ret_invoice_number'   , $type_text,	96,		['nullable' => true,'default' => null],	'Karşı yazılımda kesilen fatura numarası')
			->addColumn('ret_invoice_date'     , $type_dtime,	null,	['nullable' => true,'default' => null],	'Karşı yazılımda fatura oluşturulma zamanı')
			->addColumn('last_error_msg'       , $type_text,	236,	['nullable' => true],	'Last Error Message')

			->addIndex($setup->getIdxName('oto_order_jobs',['job_id'],\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_PRIMARY),['job_id'])
			->addIndex($setup->getIdxName('oto_order_jobs',['order_id'],\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX),['order_id'])
			->addIndex($setup->getIdxName('oto_order_jobs',['order_increment_id'],\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX),['order_increment_id'])
			->addIndex($setup->getIdxName('oto_order_jobs',['customer_id'],\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX),['customer_id'])
			->addIndex($setup->getIdxName('oto_order_jobs',['customer_email'],\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT),['customer_email'])
            
			->setComment('Oto Sipariş Entegrasyonları için ortak görev kuyruğu');

        $connection->createTable($table);


        /**
         * Create table 'oto_order_jobs_log'
         */

        $table = $connection
            ->newTable($installer->getTable('oto_order_jobs_log'))
            
			->addColumn('job_log_id',$type_int,12,['nullable' => false,'unsigned' => true,'auto_increment' => true,'primary' => true],'Job Log Id')
            ->addColumn('job_id',$type_int,10,['nullable' => false],'Job ID')
            ->addColumn('q_target',$type_text,24,['nullable' => true],'Job Target : oto...')
            ->addColumn('data_sent',$type_text,null,['nullable' => true],'Sent data')
            ->addColumn('data_response',$type_text,null,['nullable' => true],'Received data')
            ->addColumn('conn_stats',$type_text,null,['nullable' => true],'Connection statistics')
            ->addColumn('conn_success',Table::TYPE_SMALLINT,1,['default' => '0','nullable' => true,'precision' => '3'],'Connection successful ? 0:No, 1:Yes')
            ->addColumn('created_at',$type_dtime,null,['nullable' => true],'Log Tarihi')
			->setComment('Oto Sipariş Entegrasyonları için görev kuyruğu logu');
        
		$connection->createTable($table);

        $installer->endSetup();

    }
}
