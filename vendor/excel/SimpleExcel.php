<?php
namespace vendor\excel;

use vendor\helpers\CharHelper;
use vendor\exceptions\ServerErrorException;

class SimpleExcel
{
	/**
	 * @param array $data
	 * @return string 成功返回存储path，失败则返回空串
	 */
	public static function export(array $data, int $colWidth = 12)
	{
		$rowCount = count($data);
		if (!$rowCount) {
			return '';
		}
		$head = reset($data);
		$colCount = count($head);
		if (!$colCount) {
			return '';
		}
		$colCount = CharHelper::int2Chars($colCount);
		
		$phpExcel = new \PHPExcel();
		$sheet = $phpExcel->getActiveSheet();
		
		//整体格式
		$style = [
				'borders' => [
						'allborders' => [
								'style' => \PHPExcel_Style_Border::BORDER_THIN,
						],
				],
				'alignment' => [
						'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				],
		];
		$sheet->getStyle("A1:{$colCount}{$rowCount}")->applyFromArray($style);
		
		//head格式
		$headStyle = [
				'font' => [
						'bold' => true,
				],
				'alignment' => [
						'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				]
		];
		
		//第一列格式
		$sheet->getStyle("A1:{$colCount}1")->applyFromArray($headStyle);
		
		//alignment格式
		$leftStyle = [
				'alignment' => [
						'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				],
		];
		$sheet->getStyle("A1:A{$rowCount}")->applyFromArray($leftStyle);
		
		$sheet->getDefaultColumnDimension()->setWidth($colWidth);
		
		// 自动缩小
		// 		$shrinkStyle = [
		// 				'alignment' => [
		// 						'shrinkToFit' => true,
		// 				],
		// 		];
		// 		$sheet->getStyle('L1:L' . $rowCount)->applyFromArray($shrinkStyle);
		// 		$sheet->getStyle('N1:N' . $rowCount)->applyFromArray($shrinkStyle);
		
		// width 设置：C I J O P, D E F , G H K , M Q R , L , N
		// 		$sheet->getColumnDimension('C')->setWidth(11);
		// 		$sheet->getColumnDimension('I')->setWidth(13);
		// 		//设置隐藏：A B
		// 		$sheet->getColumnDimension('A')->setVisible(false);
		// 		$sheet->getColumnDimension('B')->setVisible(false);
		
		//page 设置
		$sheet->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		$sheet->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		
		//data 设置
		$sheet->fromArray($data, null);
		
		//写excel2007
		try {
			$path = @tempnam(\PHPExcel_Shared_File::sys_get_temp_dir(), 'phpxltmp');
			if ($path == '') {
				throw new ServerErrorException();
			}
			$writer = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
			$writer->save($path);
			return $path;
		} catch (\Exception $e) {
			throw new ServerErrorException();
		}
	}
}