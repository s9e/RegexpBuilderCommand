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
use UnhandledMatchError;
use s9e\RegexpBuilder\Builder;
use s9e\RegexpBuilder\Command\InputFormatter\InputFormatterInterface;

class Build extends Command
{
	protected static $defaultName = 'regexp:build';

	protected function configure(): void
	{
		$this->addOption(
			'preset',
			null,
			InputOption::VALUE_REQUIRED,
			'Regexp preset: "java", "javascript", "pcre", "raw", or "re2"',
			'pcre',
			['java', 'javascript', 'pcre', 'raw', 're2']
		);
		$this->addOption(
			'flags',
			null,
			InputOption::VALUE_REQUIRED,
			'Regexp flags',
			''
		);
		$this->addOption(
			'infile',
			null,
			InputOption::VALUE_REQUIRED,
			'Input file'
		);
		$this->addOption(
			'infile-format',
			null,
			InputOption::VALUE_REQUIRED,
			'Format of the input file: "json" or "lsv" (line-separated values)',
			'lsv',
			['json', 'lsv']
		);
		$this->addOption(
			'outfile',
			null,
			InputOption::VALUE_REQUIRED,
			'Output file',
			'-'
		);
		$this->addOption(
			'overwrite',
			null,
			InputOption::VALUE_NEGATABLE,
			'Whether to overwrite existing files',
			false
		);
		$this->addOption(
			'standalone',
			null,
			InputOption::VALUE_NEGATABLE,
			'Whether the regexp is meant to be used whole',
			true
		);
		$this->addOption(
			'with-delimiters',
			null,
			InputOption::VALUE_NONE,
			"Output the regexp's delimiter"
		);
		$this->addOption(
			'with-flags',
			null,
			InputOption::VALUE_NONE,
			"Output the regexp's flags (requires --with-delimiters)"
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
		$builder->standalone = $input->getOption('standalone');
		$regexp  = $builder->build($strings);

		if ($input->getOption('with-delimiters'))
		{
			$regexp = $config['delimiter'][0] . $regexp . $config['delimiter'][-1];
			if ($input->getOption('with-flags'))
			{
				$regexp .= $this->sortFlags($input->getOption('flags'));
			}
		}

		$filepath = $input->getOption('outfile');
		if ($filepath === '-')
		{
			$output->write($regexp);
		}
		else
		{
			$this->writeFile($filepath, $regexp, $input->getOption('overwrite'));
		}

		return Command::SUCCESS;
	}

	protected function getBuilderConfig(InputInterface $input): array
	{
		$preset  = strtolower($input->getOption('preset'));
		$unicode = str_contains($input->getOption('flags'), 'u');
		try
		{
			$config = match ($preset)
			{
				'java' => [
					'input'  => 'Utf8',
					'output' => 'PHP'
				],
				'javascript' => [
					'input'        => 'Utf8',
					'inputOptions' => ['useSurrogates' => !$unicode],
					'output'       => 'JavaScript'
				],
				'pcre' => [
					'input'  => ($unicode) ? 'Utf8' : 'Bytes',
					'output' => 'PHP'
				],
				're2' => [
					'input'  => 'Utf8',
					'output' => 'PHP'
				],
				'raw' => [
					'input'  => ($unicode) ? 'Utf8' : 'Bytes',
					'output' => ($unicode) ? 'Utf8' : 'Bytes',
				]
			};
		}
		catch (UnhandledMatchError)
		{
			throw new RuntimeException("Unknown preset '" . $preset . "'");
		}
		$config['delimiter'] = '/';

		return $config;
	}

	protected function getStrings(InputInterface $input): array
	{
		$filepath = $input->getOption('infile');
		if ($filepath === null)
		{
			return $input->getArgument('strings');
		}

		$text = ($filepath === '-') ? $this->readStdin($input) : $this->readFile($filepath);

		return $this->getInputFormatter($input)->format($text);
	}

	protected function getInputFormatter(InputInterface $input): InputFormatterInterface
	{
		$className = __NAMESPACE__ . '\\InputFormatter\\' . ucfirst(strtolower($input->getOption('infile-format')));
		if (!class_exists($className))
		{
			throw new RuntimeException('Unsupported infile-format');
		}

		return new $className;
	}

	protected function readFile(string $filepath): string
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

	protected function readStdin(InputInterface $input): string
	{
		// https://github.com/symfony/symfony/issues/37835#issuecomment-674386588
		$stream = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;

		return stream_get_contents($stream ?? STDIN);
	}

	protected function sortFlags(string $flags): string
	{
		$flags = array_unique(str_split($flags, 1));
		sort($flags, SORT_STRING);

		return implode('', $flags);
	}

	protected function writeFile(string $filepath, string $contents, bool $overwrite): void
	{
		if (file_exists($filepath))
		{
			if (!$overwrite)
			{
				throw new RuntimeException("File '" . $filepath . "' already exists and overwrite is not enabled");
			}
			if (!is_writable($filepath))
			{
				throw new RuntimeException("File '" . $filepath . "' is not writable");
			}
		}
		if (!is_writable(dirname($filepath)) || !file_put_contents($filepath, $contents))
		{
			throw new RuntimeException("File '" . $filepath . "' is not writable");
		}
	}
}