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

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
    
#[\AllowDynamicProperties]
class ListJobs extends Command
{
	/*
		@function
	*/

	function __construct(
		\Oto\OrderSync\Helper\Data				$_otoHelper,
		\Oto\OrderSync\Helper\OrderListQueue		$_orderSyncHelper
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
		$this->setName('oto:list_jobs');
		$this->setDescription('Lists latest order sync jobs');
		
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
		$orders = $this->_orderSyncHelper->listPendingOrders();

		try {

			if (is_array($orders) and count($orders) > 0) 
			{
				$table = new Table($output);

				$table->setHeaders([
									__('Order ID'),
									__('Order #'), 
									__('Customer Name'), 
									__('Customer Email'), 
									__('Grand Total'), 
									__('Status'), 
									__('Error Count'), 
								]);
				foreach ($orders as $order) {

					$table->addRow([
						@$order['entity_id'			],
						@$order['increment_id'		],
						@$order['customer_name'		],
						@$order['customer_email'	],
						@$order['grand_total'		],
						@$order['status'			],
						@$order['error_count'		],
					]);

					$table->render();

				}
				
			} // if sonu
			else 
			{
				$output->writeln(__('No order in job list'));
			} // else sonu

			return \Magento\Framework\Console\Cli::RETURN_SUCCESS;

		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
				$output->writeln($e->getTraceAsString());
			}

			return \Magento\Framework\Console\Cli::RETURN_FAILURE;
		}
	}
}

?>