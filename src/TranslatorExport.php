<?php

declare(strict_types=1);

namespace Baraja\TranslatorExport;


final class TranslatorExport
{
	/**
	 * Convert NEON files in folder to Excel file
	 *
	 * @outputFile can be *.csv or *.xlsx
	 */
	public static function neonToExcel(string $neonFilesFolder, string $outputFile): void
	{
		(new NeonToExcel)->convert($neonFilesFolder, $outputFile);
	}


	/**
	 * Covert Excel file to NEON files
	 *
	 * @param bool $emptyStrings true => possible leads to empty string in final NEON files
	 */
	public static function excelToNeon(string $inputFile, string $outputFolder, bool $emptyStrings = false): void
	{
		(new ExcelToNeon)->convert($inputFile, $outputFolder, $emptyStrings);
	}
}
