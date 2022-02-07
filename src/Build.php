<?php declare(strict_types=1);

/**
* @package   s9e\RegexpBuilderCommand
* @copyright Copyright (c) 2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\RegexpBuilder\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use s9e\RegexpBuilder\Builder;

class Build extends Command
{
	protected static $defaultName = 'regexp:build';

	protected function configure(): void
	{
		$this->addOption(
			'infile',
			null,
			InputOption::VALUE_REQUIRED,
			'Input file'
		);
		$this->addOption(
			'outfile',
			null,
			InputOption::VALUE_REQUIRED,
			'Output file',
			'-'
		);
		$this->addOption(
			'preset',
			null,
			InputOption::VALUE_REQUIRED,
			'Regexp preset: "javascript", "php", or "raw"',
			'raw'
		);
		$this->addOption(
			'unicode',
			null,
			InputOption::VALUE_NEGATABLE,
			'Whether to operate on Unicode codepoints rather than on bytes'
		);
		$this->addOption(
			'overwrite',
			null,
			InputOption::VALUE_NEGATABLE,
			'Whether to overwrite existing files'
		);

//		$this->addOption(
//			'input-mode',
//			null,
//			InputOption::VALUE_REQUIRED,
//			'Force input mode: "Bytes" or "Utf8"'
//		);
//		$this->addOption(
//			'output-mode',
//			null,
//			InputOption::VALUE_REQUIRED,
//			'Force output mode: "Bytes", "JavaScript", "PHP", or "Utf8"'
//		);
		$this->addOption(
			'delimiter',
			null,
			InputOption::VALUE_REQUIRED,
			'Character used as delimiter',
			'/'
		);
		$this->addOption(
			'standalone',
			null,
			InputOption::VALUE_NONE,
			'Whether to create a standalone regexp including the delimiters'
		);

		$this->addArgument('strings', InputArgument::IS_ARRAY);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$strings = $this->getStrings($input);
		if (empty($strings))
		{
			throw new RuntimeException('No input');
		}

		$config  = $this->getBuilderConfig($input);
		$builder = new Builder($config);
		$regexp  = $builder->build($strings);

		if ($input->getOption('standalone'))
		{
			$regexp = substr($config['delimiter'], 0, 1) . $regexp . substr($config['delimiter'], -1);
			if ($input->getOption('unicode'))
			{
				$regexp .= 'u';
			}
		}

		$filepath = $input->getOption('outfile');
		if ($filepath === '-')
		{
			$output->write($regexp);
		}
		elseif (file_exists($filepath) && !$input->getOption('overwrite'))
		{
			throw new RuntimeException("File '" . $filepath . "' already exists");

			return Command::FAILURE;
		}
		elseif (!is_writable($filepath) || !file_put_contents($filepath, $regexp))
		{
			throw new RuntimeException("Cannot write to '" . $filepath . "'");
		}

		return Command::SUCCESS;
	}

	protected function getBuilderConfig(InputInterface $input): array
	{
		$preset = strtolower($input->getOption('preset')) . ($input->getOption('unicode') ? '-unicode' : '');
		$config = match ($preset)
		{
			'javascript' => [
				'input'        => 'Utf8',
				'inputOptions' => ['useSurrogates' => true],
				'output'       => 'JavaScript'
			],
			'javascript-unicode' => [
				'input'        => 'Utf8',
				'inputOptions' => ['useSurrogates' => false],
				'output'       => 'JavaScript'
			],
			'php' => [
				'input'  => 'Bytes',
				'output' => 'PHP'
			],
			'php-unicode' => [
				'input'  => 'Utf8',
				'output' => 'PHP'
			],
			'raw' => [
				'input'  => 'Bytes',
				'output' => 'Bytes'
			],
			'raw-unicode' => [
				'input'  => 'Utf8',
				'output' => 'Utf8'
			]
		};
		$config['delimiter'] = $input->getOption('delimiter');

		return $config;
	}

	protected function getContentFromFile(string $filepath): string
	{
		if (!file_exists($filepath))
		{
			throw new RuntimeException("File '" . $filepath . "' does not exist");
		}
		if (!is_readable($filepath))
		{
			throw new RuntimeException("File '" . $filepath . "' is not readable");
		}

		return file_get_contents($filepath);
	}

	protected function getContentFromStdin(InputInterface $input): string
	{
		// https://github.com/symfony/symfony/issues/37835#issuecomment-674386588
		$stream = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;

		return stream_get_contents($stream ?? STDIN);
	}

	protected function getStrings(InputInterface $input): array
	{
		$filepath = $input->getOption('infile');
		if ($filepath === null)
		{
			return $input->getArgument('strings');
		}

		$text = ($filepath === '-')
		      ? $this->getContentFromStdin($input)
		      : $this->getContentFromFile($filepath);

		return preg_split('(\\r?\\n)', $text, -1, PREG_SPLIT_NO_EMPTY);
	}
}