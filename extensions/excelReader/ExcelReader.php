<?php

namespace extensions\ExcelReader;

use moonland\phpexcel\Excel;

class ExcelReader extends Excel
{
    /**
	 * reading the first sheet of xls file
	 */
	public function readFile($fileName)
	{
		if (!isset($this->format))
			$this->format = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fileName);
		$objectreader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($this->format);
		$objectPhpExcel = $objectreader->load($fileName);

		$sheetDatas = [];

        // we support only one sheet so set the first sheet as active sheet
        $objectPhpExcel->setActiveSheetIndex(0);

        $sheetDatas = $objectPhpExcel->getActiveSheet()->toArray(null, true, true, true);
        if ($this->setFirstRecordAsKeys) {
            $sheetDatas = $this->executeArrayLabel($sheetDatas);
        }
        if (!empty($this->getOnlyRecordByIndex)) {
            $sheetDatas = $this->executeGetOnlyRecords($sheetDatas, $this->getOnlyRecordByIndex);
        }
        if (!empty($this->leaveRecordByIndex)) {
            $sheetDatas = $this->executeLeaveRecords($sheetDatas, $this->leaveRecordByIndex);
        }

		return $sheetDatas;
	}
}