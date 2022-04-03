<?php declare(strict_types=1);

namespace s9e\RegexpBuilder\Command\Tests;

use PHPUnit\Framework\TestCase;
use s9e\RegexpBuilder\Command\InputFormatter\Json;

/**
* @covers s9e\RegexpBuilder\Command\InputFormatter\Json
*/
class JsonTest extends TestCase
{
	public function test()
	{
		$this->assertEquals(
			['foo', 'bar', 'baz'],
			(new Json)->format('["foo","bar","baz"]')
		);
	}

	public function testUnicode()
	{
		$this->assertEquals(
			["\xF0\x9F\x98\x80"],
			(new Json)->format('["\\uD83D\\uDE00"]')
		);
	}

	public function testInvalidJson()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Input is not valid JSON');

		(new Json)->format('invalid');
	}

	public function testInvalidJsonType()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Input is not a JSON array');

		(new Json)->format('{"foo":"bar"}');
	}

	public function testInvalidJsonValue()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Input contains non-string values');

		(new Json)->format('["foo",1]');
	}
}