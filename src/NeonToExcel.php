<?php

declare(strict_types=1);

namespace Baraja\TranslatorExport;


use Nette\Neon\Neon;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Spatie\SimpleExcel\SimpleExcelWriter;

final class NeonToExcel
{
	/** @var string[] */
	private array $locales = [];


	public function convert(string $localeDir, string $outputFile): void
	{
		if (is_dir($localeDir) === false) {
			throw new \InvalidArgumentException('Locale dir "' . $localeDir . '" does not exist.');
		}
		$filesGrouped = $this->loadNeonFiles($localeDir);
		$final = $this->transformArray($filesGrouped);
		$this->renderExcelFile($final, $outputFile);
	}


	/**
	 * Load and parse files in folder
	 * generating list of languages ($this->languages)
	 * output array structure: title->language->id->string
	 *
	 * @return array<string, array<string, array<string, string>>>
	 */
	private function loadNeonFiles(string $localeDir): array
	{
		$files = (array) glob($localeDir . '/*.neon');
		$return = [];
		foreach ($files as $file) {
			if (is_file($file) === false) {
				continue;
			}
			$neon = Neon::decode(FileSystem::read($file));
			if ($neon !== null) {
				$pathParts = explode('/', (string) $file);
				[$title, $locale] = explode('.', $pathParts[count($pathParts) - 1]);

				// add to the list of available languages
				if (!in_array($locale, $this->locales, true)) {
					$this->locales[] = $locale;
				}
				$return[$title][$locale] = $this->parseMultiNeon($neon);
			}
		}

		return $return;
	}


	/**
	 * Transform structure of array
	 * from: title->language->id->string
	 * into: title->id->language->string
	 *
	 * @param array<string, array<string, array<string, string>>> $filesGrouped
	 * @return array<string, array<int|string, array<string, mixed>>>
	 */
	private function transformArray(array $filesGrouped): array
	{
		$return = [];
		foreach ($filesGrouped as $title => $temp) {
			$return[$title] = [];
			foreach ($this->locales as $language) {
				$lastId = null;
				foreach ($temp[$language] as $id => $value) {
					if (!isset($return[$title][$id])) {
						if ($lastId !== null) {
							Arrays::insertAfter($return[$title], $lastId, [$id => []]);
						} else {
							$return[$title][$id] = [];
						}
					}
					$return[$title][$id][$language] = $value;
					$lastId = $id;
				}
			}
		}

		return $return;
	}


	/**
	 * @param array<string, array<int|string, array<string, mixed>>> $finalArray
	 */
	private function renderExcelFile(array $finalArray, string $outputFile): void
	{
		$writer = SimpleExcelWriter::create($outputFile);
		foreach ($finalArray as $title => $section) {
			foreach ($section as $id => $row) {
				$excelRow = [
					'domain' => $title,
					'id' => $id,
				];
				foreach ($this->locales as $language) {
					if (isset($row[$language])) {
						$excelRow[$language] = $row[$language];
					} else {
						$excelRow[$language] = '';
					}
				}
				$writer->addRow($excelRow);
			}
		}
	}


	/**
	 * Convert multidimensional array into: xx.yy.zz => (string) value
	 *
	 * @param array<string, mixed> $array
	 * @return array<string, string>
	 */
	private function parseMultiNeon(array $array): array
	{
		$return = [];
		foreach ($this->getKeys($array) as $key) {
			$findValue = $array;
			foreach (explode('.', $key) as $subKey) {
				$findValue = $findValue[$subKey];
			}
			$return[$key] = $findValue;
		}

		return $return;
	}


	/**
	 * Get keys of multidimensional array like xx.yy.zz
	 *
	 * @param array<string, mixed> $haystack
	 * @return array<int, string>
	 */
	private function getKeys(array $haystack): array
	{
		$return = [];
		foreach (array_keys($haystack) as $key) {
			if (is_array($haystack[$key])) {
				foreach ($this->getKeys($haystack[$key]) as $sub) {
					$return[] = $key . '.' . $sub;
				}
			} else {
				$return[] = $key;
			}
		}

		return $return;
	}
}
