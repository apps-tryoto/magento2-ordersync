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

namespace Oto\OrderSync\Block\Adminhtml\OrderSync;

#[\AllowDynamicProperties]
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

	protected $_registry;
    protected $_objectManager;
    protected $_orderSyncResource;
    protected $backendHelper;
    protected $objectManager;
    protected $context;

    
	public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $_registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Oto\OrderSync\Model\OrderSync $_orderSyncResource,
        array $data = []
    )
	{
        $this->_objectManager = $objectManager;
        $this->_registry = $_registry;
        $this->_orderSyncResource = $_orderSyncResource;

        parent::__construct($context, $backendHelper, $data);
    }
    
	protected function _construct()
    {
        parent::_construct();
        $this->setId('job_id');
        $this->setDefaultSort('job_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
	protected function _prepareCollection()
    {
        $orderSync = $this->_orderSyncResource->getCollection()
            ->addFieldToSelect('*');
        $orderSync->addFieldToFilter('job_id', array('neq' => ''));
        $this->setCollection($orderSync);
        return parent::_prepareCollection();
    }
    
	protected function _prepareColumns()
    {
        $job_targets = $this->_orderSyncResource->getCollection()->addFieldToSelect('job_target');
        $job_targets->addFieldToFilter('job_target', array('neq' => ''));
        $job_targets->getSelect()->group('job_target');
		
		$options_job_target = [];

		foreach ($job_targets as $target) 
		{
			$options_target[$target['job_target']] = __('target_'.$target['job_target']);
		} // foreach sonu

		$options_type = [
							'new_order'            => __('New Order'), 
							'cancel_order'         => __('Cancel Order'), 
							'refund_order'         => __('Full Refund'), 
							'partial_refund_order' => __('Partial Refund'), 
							];
		
		$options_status = [
							'WAITING'		=> __('Waiting'), 
							'IN_PROCESS'	=> __('In Process'), 
							'SUCCESS'		=> __('Done'), 
							'ERROR'			=> __('Error'), 
							'SUSPENDED'		=> __('Suspended'), 
							];
		
		$this->addColumn( 'job_id_sel'          , ['header' => __('#'),					'header_css_class' => 'a-center',	'type' => 'checkbox',	'name' => 'job_id'                ,'align' => 'center',	'index' => 'job_id'                ,'sortable' => false,	] ); 
		$this->addColumn( 'job_id'              , ['header' => __('Job Id'),			'header_css_class' => 'a-center',	'type' => 'number',		'name' => 'job_id'                ,'align' => 'center',	'index' => 'job_id'                ,'sortable' => true,	] ); 
		$this->addColumn( 'job_type'            , ['header' => __('Job Type'),			'header_css_class' => 'a-left',		'type' => 'options',	'name' => 'job_type'            ,'align' => 'center',	'index' => 'job_type'            ,'options' => $options_type,] ); 
		$this->addColumn( 'order_id'            , ['header' => __('Order Id'),			'header_css_class' => 'a-center',	'type' => 'text',		'name' => 'order_id'            ,'align' => 'center',	'index' => 'order_id'            ,] ); 
		$this->addColumn( 'order_increment_id'  , ['header' => __('Order No'),			'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'order_increment_id'  ,'align' => 'center',	'index' => 'order_increment_id'  ,] ); 
		$this->addColumn( 'customer_id'         , ['header' => __('Customer Id'),		'header_css_class' => 'a-center',	'type' => 'text',		'name' => 'customer_id'         ,'align' => 'center',	'index' => 'customer_id'         ,] ); 
		$this->addColumn( 'customer_name'       , ['header' => __('Customer Name'),		'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'customer_name'       ,'align' => 'center',	'index' => 'customer_name'       ,] ); 
		$this->addColumn( 'customer_email'      , ['header' => __('Customer Email'),	'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'customer_email'      ,'align' => 'center',	'index' => 'customer_email'      ,] ); 
		$this->addColumn( 'created_at'          , ['header' => __('Created At'),		'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'created_at'          ,'align' => 'center',	'index' => 'created_at'          ,] ); 
		//$this->addColumn( 'updated_at'        , ['header' => __('Updated At'),		'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'updated_at'          ,'align' => 'center',	'index' => 'updated_at'          ,] ); 
		$this->addColumn( 'job_status'          , ['header' => __('Status'),			'header_css_class' => 'a-left',		'type' => 'options',	'name' => 'job_status'          ,'align' => 'center',	'index' => 'job_status'          ,'options' => $options_status,]); 
		$this->addColumn( 'error_count'         , ['header' => __('Error Count'),		'header_css_class' => 'a-right',	'type' => 'text',		'name' => 'error_count'         ,'align' => 'center',	'index' => 'error_count'         ,] ); 
		//$this->addColumn( 'returned_data'     , ['header' => __('Response'),			'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'returned_data'       ,'align' => 'center',    'sortable' => false,] ); 
		$this->addColumn( 'ret_order_id'		, ['header' => __('Target Order #'),	'header_css_class' => 'a-right',	'type' => 'text',		'name' => 'ret_order_id'        ,'align' => 'center',	'index' => 'ret_order_id'        ,] ); 
		//$this->addColumn( 'ret_account_id'    , ['header' => __('Target Cust. #'),	'header_css_class' => 'a-right',	'type' => 'text',		'name' => 'ret_account_id'      ,'align' => 'center',	'index' => 'ret_account_id'      ,] ); 
		//$this->addColumn( 'last_error_msg'    , ['header' => __('Last Error'),		'header_css_class' => 'a-left',		'type' => 'text',		'name' => 'last_error_msg'      ,'align' => 'center',	'index' => 'last_error_msg'      ,] ); 

		$actions = [
			[
				'caption' => __('View'),
				'url' => ['base' => 'oto/OrderSync/View'],
				'field'   => 'job_id', 
			],
			[
				'caption' => __('Reset Error Count'),
				'url' => ['base' => 'oto/OrderSync/ResetErrorCount'],
				'field'   => 'job_id', 
			]
		];

        $this->addColumn(
            'action',
            [
                'header'	=> __('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => $actions,
                'filter'    => false,
                'sortable'  => false,
                'index'		=> 'job_id',
                'is_system' => true
            ]
        );
        return parent::_prepareColumns();
    }

	public function getRowUrl($row)
	{
		return $this->getUrl(
			'oto/OrderSync/View',
			['job_id' => $row->getId()]
		);
	}

    public function getGridUrl()
    {
        return $this->getUrl('oto/Grid/OrderSync', ['_current' => true]);
    }

    /**
     * @return void
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    /**
     * @param \Magento\Framework\Data\Collection $collection
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _filterStoreCondition($collection, $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

}