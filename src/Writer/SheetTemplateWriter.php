<?php


namespace Sigmasolutions\Sheets\Writer;


use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SheetTemplateWriter
{
    /**
     * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    private $spreadsheet;

    /**
     * SheetTemplateWriter constructor.
     * @param string $templatePath
     */
    public function __construct(string $templatePath)
    {
        $this->spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
    }

    public static function stringyfyValues($rows)
    {
        for ($i = 0; $i < count($rows); $i++) {
            for ($j = 0; $j < count($rows[$i]); $j++) {
                if (is_null($rows[$i][$j])) {
                    $rows[$i][$j] = '';
                } else {
                    $rows[$i][$j] = "" . $rows[$i][$j] . "";
                }
            }
        }
        return $rows;
    }

    /**
     * @param $templatePath
     * @return SheetTemplateWriter
     */
    public static function open($templatePath): SheetTemplateWriter
    {
        return new SheetTemplateWriter($templatePath);
    }

    /**
     * @param $sheetName
     * @param array $rows
     * @param string $startCell
     * @return SheetTemplateWriter
     */
    public function writeToSheetByName($sheetName, array $rows, $startCell = 'A1'): SheetTemplateWriter
    {
        $rows = static::stringyfyValues($rows);
        $this->spreadsheet->getSheetByName($sheetName)
            ->fromArray(
                $rows,
                NULL,
                $startCell
            );
        return $this;
    }

    /**
     * @param $sheetIndex
     * @param array $rows
     * @param string $startCell
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function writeToSheet($sheetIndex, array $rows, $startCell = 'A1'): SheetTemplateWriter
    {
        $rows = static::stringyfyValues($rows);
        $this->spreadsheet->getSheet($sheetIndex)
            ->fromArray(
                $rows,
                null,
                $startCell
            );
        return $this;
    }

    /**
     * @param $filePath
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function save($filePath)
    {
        (new Xlsx($this->spreadsheet))->save($filePath);
    }
}
