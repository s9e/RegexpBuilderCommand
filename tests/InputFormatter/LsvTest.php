<?php declare(strict_types=1);

namespace s9e\RegexpBuilder\Command\Tests;

use PHPUnit\Framework\TestCase;
use s9e\RegexpBuilder\Command\InputFormatter\Lsv;

/**
* @covers s9e\RegexpBuilder\Command\InputFormatter\Lsv
*/
class LsvTest extends TestCase
{
	public function test()
	{
		$this->assertEquals(
			['foo', 'bar', 'baz'],
			(new Lsv)->format("foo\nbar\r\nbaz")
		);
	}
}