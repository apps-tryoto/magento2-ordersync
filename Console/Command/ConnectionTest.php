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
class ConnectionTest extends Command
{
	// -------------------------------------------------------------------------------------------------------
	function __construct(
		\Oto\OrderSync\Helper\Data			$_otoHelper,
		\Oto\OrderSync\Helper\OrderSync		$_orderSyncHelper
	) {
		
		$this->_otoHelper		= $_otoHelper;
		$this->_orderSyncHelper = $_orderSyncHelper;

		parent::__construct();

	} // function sonu ---------------------------------------------------------------------------------------

	/**
	 * @inheritDoc
	 */
	protected function configure()
	{
		$this->setName('oto:connection_test');
		$this->setDescription('Tests connection between your server and Oto Api server');
		
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
		$osHelper = $this->_orderSyncHelper;
		$helper = $this->_otoHelper;
		
		$output->writeln('');

		$this->api_url

		if ($token != '' and $token !== false) 
		{
			$masked_token = mb_substr($token,0,30)." ... ".mb_substr($token,-30);
			$output->writeln((string) __('Oto Token : %1',$masked_token));
			$output->writeln("\n");
			$output->writeln('<info>' . (string) __('Connection Successful') . '</info>');
			$output->writeln("\n");
			return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
		} // if sonu
		else 
		{
			$output->writeln('<error>' . (string) __('Connection Failed') . '</error>');
			$output->writeln("\n");
			return \Magento\Framework\Console\Cli::RETURN_FAILURE;
		} // else sonu
		
	}
}

?>