<?php declare(strict_types=1);

namespace s9e\RegexpBuilder\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use s9e\RegexpBuilder\Command\Build;

/**
* @covers s9e\RegexpBuilder\Command\Build
*/
class BuildTest extends TestCase
{
	/**
	* @dataProvider getBuildTests
	*/
	public function test(array $input, string $expectedOutput)
	{
		$commandTester = new CommandTester(new Build);
		$commandTester->execute($input);

		$commandTester->assertCommandIsSuccessful();

		$this->assertEquals($expectedOutput, $commandTester->getDisplay());
	}

	public function getBuildTests()
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
					'strings' => ["\u{1F600}", "\u{1F601}"]
				],
				"\xF0\x9F\x98[\x80\x81]"
			],
			[
				[
					'strings'   => ["\u{1F600}", "\u{1F601}"],
					'--unicode' => true
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
					'strings'   => ["\u{1F600}", "\u{1F601}"],
					'--preset'  => 'pcre',
					'--unicode' => true
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
					'strings'   => ["\u{1F600}", "\u{1F601}"],
					'--preset'  => 'javascript',
					'--unicode' => true
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
					'--preset'     => 'javascript',
					'--standalone' => true,
					'--unicode'    => true
				],
				'/[\\u{1F600}\\u{1F601}]/u'
			],
		];
	}
}