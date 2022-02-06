<?php declare(strict_types=1);

/**
* @package   s9e\RegexpBuilderCommand
* @copyright Copyright (c) 2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\RegexpBuilder\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use s9e\RegexpBuilder\Builder;

class Build extends Command
{
	protected static $defaultName = 'regexp:build';

	protected function configure(): void
	{
		$this->addOption('input-mode',  null, InputOption::VALUE_REQUIRED, 'Input mode:  "Bytes" or "Utf8"', 'Bytes');
		$this->addOption('output-mode', null, InputOption::VALUE_REQUIRED, 'Output mode: "Bytes", "JavaScript", "PHP", or "Utf8"', 'Bytes');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$builder = new Builder;

		return Command::SUCCESS;
	}
}