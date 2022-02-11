<?php declare(strict_types=1);

namespace s9e\RegexpBuilder\Command\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use s9e\RegexpBuilder\Command\Build;

/**
* @covers s9e\RegexpBuilder\Command\Build
*/
class BuildTest extends TestCase
{
	protected static $tmpfiles = [];

	public static function tearDownAfterClass(): void
	{
		foreach (self::$tmpfiles as $path)
		{
			if (file_exists($path))
			{
				unlink($path);
			}
		}
		self::$tmpfiles = [];
	}

	protected function tmpfile(string $path)
	{
		return self::$tmpfiles[] = $path;
	}

	public function testWriteFile()
	{
		$filepath = sys_get_temp_dir() . '/out.txt';
		self::$tmpfiles[] = $filepath;
		if (file_exists($filepath))
		{
			unlink($filepath);
		}

		$commandTester = new CommandTester(new Build);
		$commandTester->execute([
			'strings'   => ['x', 'y'],
			'--outfile' => $filepath
		]);
		$commandTester->assertCommandIsSuccessful();

		$this->assertFileExists($filepath);
		$this->assertEquals('[xy]', file_get_contents($filepath));
	}

	public function testWriteFileFailure()
	{
		$dir = uniqid(sys_get_temp_dir() . '/');
		if (!file_exists($dir))
		{
			mkdir($dir);
		}
		chmod($dir, 0555);

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("File '" . $dir . "/foo.txt' is not writable");

		try
		{
			$commandTester = new CommandTester(new Build);
			$commandTester->execute([
				'strings'   => ['x', 'y'],
				'--outfile' => $dir . '/foo.txt'
			]);
		}
		catch (Exception $e)
		{
			throw $e;
		}
		finally
		{
			rmdir($dir);
		}
	}

	/**
	* @dataProvider getSuccessTests
	*/
	public function testSuccess(array $input, string $expectedOutput, callable $setup = null): void
	{
		$commandTester = new CommandTester(new Build);
		if (isset($setup))
		{
			$setup($commandTester);
		}
		$commandTester->execute($input);

		$commandTester->assertCommandIsSuccessful();

		$this->assertEquals($expectedOutput, $commandTester->getDisplay());
	}

	public function getSuccessTests(): array
	{
		return [
			[
				[
					'strings' => ['foo', 'bar']
				],
				'(?:bar|foo)'
			],
			[
				[
					'strings'      => ['foo', 'bar'],
					'--standalone' => true
				],
				'/(?:bar|foo)/'
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--preset' => 'raw'
				],
				"\xF0\x9F\x98[\x80\x81]"
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--preset' => 'raw',
					'--flags'  => 'u'
				],
				"[\u{1F600}\u{1F601}]"
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--preset' => 'pcre'
				],
				'\\xF0\\x9F\\x98[\\x80\\x81]'
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--flags'  => 'u',
					'--preset' => 'pcre'
				],
				'[\\x{1F600}\\x{1F601}]'
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--preset' => 'javascript'
				],
				'\\uD83D[\\uDE00\\uDE01]'
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--flags'  => 'u',
					'--preset' => 'javascript'
				],
				'[\\u{1F600}\\u{1F601}]'
			],
			[
				[
					'strings'      => ["\u{1F600}", "\u{1F601}"],
					'--preset'     => 'javascript',
					'--standalone' => true
				],
				'/\\uD83D[\\uDE00\\uDE01]/'
			],
			[
				[
					'strings'      => ["\u{1F600}", "\u{1F601}"],
					'--flags'      => 'u',
					'--preset'     => 'javascript',
					'--standalone' => true
				],
				'/[\\u{1F600}\\u{1F601}]/u'
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--preset' => 'java'
				],
				'[\\x{1F600}\\x{1F601}]'
			],
			[
				[
					'strings'  => ["\u{1F600}", "\u{1F601}"],
					'--preset' => 're2'
				],
				'[\\x{1F600}\\x{1F601}]'
			],
			[
				[
					'--infile' => $this->tmpfile(sys_get_temp_dir() . '/strings.txt')
				],
				'[ab]',
				function (CommandTester $commandTester): void
				{
					file_put_contents($this->tmpfile(sys_get_temp_dir() . '/strings.txt'), "a\nb");
				}
			],
			[
				[
					'--infile' => '-'
				],
				'[ab]',
				function (CommandTester $commandTester): void
				{
					$commandTester->setInputs(["a\nb"]);
				}
			],
		];
	}

	/**
	* @dataProvider getFailureTests
	*/
	public function testFailure(array $input, Exception $exception, callable $setup = null): never
	{
		$this->expectException(get_class($exception));
		$this->expectExceptionMessage($exception->getMessage());

		$commandTester = new CommandTester(new Build);
		if (isset($setup))
		{
			$setup($commandTester);
		}
		$commandTester->execute($input);
	}


	public function getFailureTests(): array
	{
		$tmpDir = sys_get_temp_dir();

		return [
			[
				[],
				new RuntimeException('No input')
			],
			[
				[
					'strings' => []
				],
				new RuntimeException('No input')
			],
			[
				[
					'strings'  => ['x'],
					'--preset' => 'unknown'
				],
				new RuntimeException("Unknown preset 'unknown'")
			],
			[
				[
					'--infile' => $tmpDir . '/unknown.txt'
				],
				new RuntimeException("File '" . $tmpDir . "/unknown.txt' does not exist")
			],
			[
				[
					'--infile' => $tmpDir . '/unreadable.txt'
				],
				new RuntimeException("File '" . $tmpDir . "/unreadable.txt' is not readable"),
				function () use ($tmpDir)
				{
					$path = $this->tmpfile($tmpDir . '/unreadable.txt');
					touch($path);
					chmod($path, 0);
				}
			],
			[
				[
					'--infile' => $tmpDir . '/empty.txt'
				],
				new RuntimeException('No input'),
				function () use ($tmpDir)
				{
					touch($this->tmpfile($tmpDir . '/empty.txt'));
				}
			],
			[
				[
					'strings'   => ['foo'],
					'--outfile' => $tmpDir . '/exists.txt'
				],
				new RuntimeException("File '" . $tmpDir . "/exists.txt' already exists"),
				function () use ($tmpDir)
				{
					touch($this->tmpfile($tmpDir . '/exists.txt'));
				}
			],
			[
				[
					'strings'     => ['foo'],
					'--overwrite' => true,
					'--outfile'   => $tmpDir . '/unwritable.txt'
				],
				new RuntimeException("File '" . $tmpDir . "/unwritable.txt' is not writable"),
				function () use ($tmpDir)
				{
					$path = $this->tmpfile($tmpDir . '/unwritable.txt');
					touch($path);
					chmod($path, 0);
				}
			],
		];
	}
}