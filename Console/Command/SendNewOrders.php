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

namespace Oto\OrderSync\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\AllowDynamicProperties]
class SendNewOrders extends Command
{

	public $_otoHelper;
	public $_orderSyncHelper;

	/*
		@function
	*/

	function __construct(
		\Oto\OrderSync\Helper\Data			$_otoHelper,
		\Oto\OrderSync\Helper\OrderSyncNew	$_orderSyncHelper
	) {
		
		$this->_otoHelper		= $_otoHelper;
		$this->_orderSyncHelper = $_orderSyncHelper;

		parent::__construct();

	} // eof func
	
	
	/**
	 * @inheritDoc
	 */
	protected function configure()
	{
		$this->setName('oto:new_order_sync');
		$this->setDescription('Send new orders to Oto');

		$this->addOption(
						'order',
						'o',
						InputOption::VALUE_OPTIONAL,
						'If you want to run specific order use long order id ( increment_id )'
					);
		
		$this->addOption(
						'order-id',
						'i',
						InputOption::VALUE_OPTIONAL,
						'If you want to run specific order use short order id ( entity_id or order_id )'
					);

		$this->addOption(
						'force-order',
						false,
						InputOption::VALUE_NONE,
						'Overwrite order if exists on OTO side'
					);

		$this->addOption(
						'debug-order',
						false,
						InputOption::VALUE_NONE,
						'Use this parameter to viewing sent/received data for debug purposes'
					);

		parent::configure();
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return null|int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		//$order = $input->getArgument('order'); 
		$params = [];
			
		$params['order']			= $input->getOption('order');
		$params['order-id']			= $input->getOption('order-id');
		$params['force-order']		= $input->getOption('force-order');
		$params['debug-order']		= $input->getOption('debug-order');
		$params['output']			= $output;
	
		if ($this->_otoHelper->getConfig('oto_ordersync/order_settings/is_active') != '1') 
		{
			$output->writeln("\n<error>".__('Order synchronization is disabled. You can turn it on from auto settings.')."</error>\n");
			return;
		} // if sonu

		$output->writeln('<info>'.__('Order synchronization is started... ').'</info>');

		$this->_orderSyncHelper->syncOrders($params,$output);	

		return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
	}
}

?>