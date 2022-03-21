<?php

declare(strict_types=1);

namespace Baraja\TranslatorExport;


use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Spatie\SimpleExcel\SimpleExcelReader;

class ExcelToNeon
{
	/** @var array<string, array<string, mixed>> */
	private array $output = [];

	private bool $emptyStrings;


	public function convert(string $file, string $outputFolder, bool $emptyStrings = false): void
	{
		$this->emptyStrings = $emptyStrings;
		$this->output = [];

		$rows = SimpleExcelReader::create($file)->getRows();
		$rows->each(
			function (array $row): void {
				if (!isset($this->output[$row['domain']])) {
					$this->output[$row['domain']] = [];
				}
				foreach ($row as $column => $value) {
					if ($column !== 'domain' && $column !== 'id') {
						$lang = $column;
						if (!isset($this->output[$row['domain']][$lang])) {
							$this->output[$row['domain']][$lang] = [];
						}
						if (!$value || $this->emptyStrings) {
							$array = $this->toArray(explode('.', $row['id']), $value);
							$this->output[$row['domain']][$lang] = $this->mergeTree(
								$this->output[$row['domain']][$lang],
								$array,
							);
						}
					}
				}
			},
		);
		/** @phpstan-ignore-next-line */
		foreach ($this->output as $fileItem => $rest) {
			foreach ($rest as $language => $array) {
				/** @phpstan-ignore-next-line */
				if (!empty($array)) {
					$neon = Neon::encode($array, Neon::BLOCK);
					FileSystem::write($outputFolder . '/' . $fileItem . '.' . $language . '.neon', $neon);
				}
			}
		}
	}


	/**
	 * Create multidimensional array
	 * credits:
	 * https://www.daniweb.com/programming/web-development/threads/476988/create-multidimensional-array-from-array-of-keys-and-a-value
	 * @param array<int, string> $keys
	 * @return array<string, mixed>
	 */
	private function toArray(array $keys, mixed $value): array
	{
		$return = [];
		$index = array_shift($keys);
		if (!isset($keys[0])) {
			$return[$index] = $value;
		} else {
			$return[$index] = $this->toArray($keys, $value);
		}

		return $return;
	}


	/**
	 * Recursively merges two fields. It is useful, for example, for merging tree structures. It behaves as
	 * the + operator for array, ie. it adds a key/value pair from the second array to the first one and retains
	 * the value from the first array in the case of a key collision.
	 *
	 * @param mixed[] $array1
	 * @param mixed[] $array2
	 * @return mixed[]
	 */
	private function mergeTree(array $array1, array $array2): array
	{
		$res = $array1 + $array2;
		foreach (array_intersect_key($array1, $array2) as $k => $v) {
			if (is_array($v) && is_array($array2[$k])) {
				$res[$k] = $this->mergeTree($v, $array2[$k]);
			}
		}

		return $res;
	}
}
