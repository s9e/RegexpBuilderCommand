<?php declare(strict_types=1);

namespace s9e\RegexpBuilder\Command\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use org\bovigo\vfs\vfsStream;
use s9e\RegexpBuilder\Command\Build;

/**
* @covers s9e\RegexpBuilder\Command\Build
*/
class BuildTest extends TestCase
{

	public function testWriteFile()
	{
		vfsStream::setup('root');
		$filepath = vfsStream::url('root/out.txt');

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
		vfsStream::setup('root');
		mkdir(vfsStream::url('root/unwritable'));
		chmod(vfsStream::url('root/unwritable'), 0555);
		$filepath = vfsStream::url('root/unwritable/file.txt');

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("File '" . $filepath . "' is not writable");

		$commandTester = new CommandTester(new Build);
		$commandTester->execute([
			'strings'   => ['x', 'y'],
			'--outfile' => $filepath
		]);
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
					'--infile' => vfsStream::url('root/strings.txt')
				],
				'[ab]',
				function (CommandTester $commandTester): void
				{
					file_put_contents(vfsStream::url('root/strings.txt'), "a\nb");
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
		vfsStream::setup('root');

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
					'--infile' => vfsStream::url('root/unknown.txt')
				],
				new RuntimeException("File '" . vfsStream::url('root/unknown.txt') . "' does not exist")
			],
			[
				[
					'--infile' => vfsStream::url('root/unreadable.txt')
				],
				new RuntimeException("File '" . vfsStream::url('root/unreadable.txt') . "' is not readable"),
				function ()
				{
					$path = vfsStream::url('root/unreadable.txt');
					touch($path);
					chmod($path, 0);
				}
			],
			[
				[
					'--infile' => vfsStream::url('root/empty.txt')
				],
				new RuntimeException('No input'),
				function ()
				{
					touch(vfsStream::url('root/empty.txt'));
				}
			],
			[
				[
					'strings'   => ['foo'],
					'--outfile' => vfsStream::url('root/exists.txt')
				],
				new RuntimeException("File '" . vfsStream::url('root/exists.txt') . "' already exists"),
				function ()
				{
					touch(vfsStream::url('root/exists.txt'));
				}
			],
			[
				[
					'strings'     => ['foo'],
					'--overwrite' => true,
					'--outfile'   => vfsStream::url('root/unwritable.txt')
				],
				new RuntimeException("File '" . vfsStream::url('root/unwritable.txt') . "' is not writable"),
				function ()
				{
					$path = vfsStream::url('root/unwritable.txt');
					touch($path);
					chmod($path, 0);
				}
			],
		];
	}
}