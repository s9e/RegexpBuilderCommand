<?php declare(strict_types=1);

/**
* @package   s9e\RegexpBuilderCommand
* @copyright Copyright (c) 2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\RegexpBuilder\Command\InputFormatter;

class Lsv implements InputFormatterInterface
{
	public function format(string $text): array
	{
		return preg_split('(\\r?\\n)', $text, -1, PREG_SPLIT_NO_EMPTY);
	}
}