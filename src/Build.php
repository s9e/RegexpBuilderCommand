<?php declare(strict_types=1);

/**
* @package   s9e\RegexpBuilderCommand
* @copyright Copyright (c) 2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\RegexpBuilder\Command;

use ReflectionMethod;
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
			'Regexp preset: "java", "javascript", "pcre", "pcre2", "raw", or "re2"',
			'pcre2',
			['java', 'javascript', 'pcre', 'pcre2', 'raw', 're2']
		);
		$this->addOption(
			'flags',
			null,
			InputOption::VALUE_REQUIRED,
			'Regexp flags/modifiers',
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

		$builder = $this->getBuilder($input->getOptions());
		$regexp  = $builder->build($strings);

		if ($input->getOption('with-delimiters'))
		{
			$regexp = '/' . $regexp . '/';
			if ($input->getOption('with-flags'))
			{
				$regexp .= count_chars($input->getOption('flags'), 3);
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

	protected function getBuilder(array $options): Builder
	{
		$options['modifiers'] = $options['flags'];

		try
		{
			$factoryName = match (strtolower($options['preset']))
			{
				'java'       => 'Java',
				'javascript' => 'JavaScript',
				'pcre',
				'pcre2',
				'php'        => 'PHP',
				're2'        => 'RE2',
				'raw'        => (str_contains($options['flags'], 'u')) ? 'RawUTF8' : 'RawBytes'
			};
		}
		catch (UnhandledMatchError)
		{
			throw new RuntimeException("Unknown preset '" . $options['preset'] . "'");
		}

		$className  = 's9e\\RegexpBuilder\\Factory\\' . $factoryName;
		$callback   = $className . '::getBuilder';
		$invokeArgs = [];

		$reflectionMethod = new ReflectionMethod($callback);
		foreach ($reflectionMethod->getParameters() as $parameter)
		{
			if (isset($options[$parameter->name]))
			{
				$invokeArgs[$parameter->name] = $options[$parameter->name];
			}
		}

		$builder = $reflectionMethod->invokeArgs(null, $invokeArgs);
		$builder->standalone = !empty($options['standalone']);

		return $builder;
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