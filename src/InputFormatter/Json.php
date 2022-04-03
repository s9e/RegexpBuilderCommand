<?php declare(strict_types=1);

/**
* @package   s9e\RegexpBuilderCommand
* @copyright Copyright (c) 2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\RegexpBuilder\Command\InputFormatter;

use RuntimeException;

class Json implements InputFormatterInterface
{
	public function format(string $text): array
	{
		$values = json_decode($text);
		if ($values === null)
		{
			throw new RuntimeException('Input is not valid JSON');
		}
		if (!is_array($values))
		{
			throw new RuntimeException('Input is not a JSON array');
		}
		foreach ($values as $value)
		{
			if (!is_string($value))
			{
				throw new RuntimeException('Input contains non-string values');
			}
		}

		return $values;
	}
}