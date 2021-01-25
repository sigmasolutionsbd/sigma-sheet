<?php declare(strict_types=1);

namespace Sigmasolutions\Sheets\Tests\Unit;


use PHPUnit\Framework\TestCase;
use Sigmasolutions\Sheets\Reader\SheetReader;
use Sigmasolutions\Sheets\Writer\SheetTemplateWriter;


class SheetTemplateWriterTest extends TestCase
{
    private $resourcesPath = 'tests/resources';

    private $templatePath;

    private $sheetDataSet1 = [
        ["", '2010', 2011, 2012],
        ['Q1', 12, 15, 21, 10, 10, -100],
        ['Q2', 56, 73, '', null, null, '', 'value'],
        [null, 'Q3', 52.3],
        ['Q4', 30, 32, 0, null, -1.2, -30],
    ];

    private $sheetDataSet2 = [
        ['null', 0, -1, -1.2, 1.3],
        ['hello', 'world', 'first', 'second'],
    ];

    private function getTemporaryFilePath()
    {
        return stream_get_meta_data(tmpfile())['uri'];
    }

    /**
     * @test
     */
    public function it_writes_to_single_sheet()
    {
        $filePath = $this->getTemporaryFilePath();
        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheet(
                0,
                $this->sheetDataSet1,
                "A3")
            ->save($filePath);

        $data = SheetReader::openFileAsXLSX($filePath)
            ->setSheetIndexs(0)
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6, 7]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([
            ['', '2010', '2011', '2012', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q1', '12', '15', '21', '10', '10', '-100', 'sheet1-default'],
            ['Q2', '56', '73', '', 'sheet1-default', 'sheet1-default', '', 'value'],
            ['sheet1-default', 'Q3', '52.3', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q4', '30', '32', '0', 'sheet1-default', '-1.2', '-30', 'sheet1-default']
        ],
            $data);
    }

    /**
     * @test
     */
    public function it_writes_single_sheet_by_name()
    {
        $filePath = $this->getTemporaryFilePath();
        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheetByName(
                'Sheet-1',
                $this->sheetDataSet1,
                "A3")
            ->save($filePath);

        $data = SheetReader::openFileAsXLSX($filePath)
            ->setSheetNames('Sheet-1')
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6, 7]);
        $data = array_slice($data, 0, 6);
        $this->assertEquals([
            ['', '2010', '2011', '2012', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q1', '12', '15', '21', '10', '10', '-100', 'sheet1-default'],
            ['Q2', '56', '73', '', 'sheet1-default', 'sheet1-default', '', 'value'],
            ['sheet1-default', 'Q3', '52.3', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q4', '30', '32', '0', 'sheet1-default', '-1.2', '-30', 'sheet1-default'],
            ['sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default']
        ],
            $data);
    }

    /**
     * @test
     */
    public function it_writes_multiple_sheet()
    {
        $filePath = $this->getTemporaryFilePath();
        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheet(
                0,
                $this->sheetDataSet1,
                "A3")
            ->writeToSheet(
                2,
                $this->sheetDataSet2,
                "A3")
            ->save($filePath);

        $sheetData = SheetReader::openFileAsXLSX($filePath)
            ->setSheetIndexs([0, 2])
            ->skipInitialRows(1)
            ->getRows([0, 1, 2, 3, 4, 5, 6, 7]);

        $this->assertEquals([
            ['sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['', '2010', '2011', '2012', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q1', '12', '15', '21', '10', '10', '-100', 'sheet1-default'],
            ['Q2', '56', '73', '', 'sheet1-default', 'sheet1-default', '', 'value'],
            ['sheet1-default', 'Q3', '52.3', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q4', '30', '32', '0', 'sheet1-default', '-1.2', '-30', 'sheet1-default'],
            ['sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default']
        ], array_slice($sheetData[0], 0, 7));

        $this->assertEquals([
            ['sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default'],
            ['null', '0', '-1', '-1.2', '1.3', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default'],
            ['hello', 'world', 'first', 'second', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default'],
            ['sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default']
        ], array_slice($sheetData[2], 0, 4));
    }

    /**
     * @test
     */
    public function it_writes_multiple_sheet_by_name()
    {
        $filePath = $this->getTemporaryFilePath();
        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheetByName(
                'Sheet-1',
                $this->sheetDataSet1,
                "A3")
            ->writeToSheet(
                2,
                $this->sheetDataSet2,
                "A3")
            ->save($filePath);

        $sheetData = SheetReader::openFileAsXLSX($filePath)
            ->setSheetNames(['Sheet-1', 'Sheet-3'])
            ->skipInitialRows(1)
            ->getRows([0, 1, 2, 3, 4, 5, 6, 7]);

        $this->assertEquals([
            ['sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['', '2010', '2011', '2012', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q1', '12', '15', '21', '10', '10', '-100', 'sheet1-default'],
            ['Q2', '56', '73', '', 'sheet1-default', 'sheet1-default', '', 'value'],
            ['sheet1-default', 'Q3', '52.3', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default'],
            ['Q4', '30', '32', '0', 'sheet1-default', '-1.2', '-30', 'sheet1-default'],
            ['sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default', 'sheet1-default']
        ], array_slice($sheetData['Sheet-1'], 0, 7));

        $this->assertEquals([
            ['sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default'],
            ['null', '0', '-1', '-1.2', '1.3', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default'],
            ['hello', 'world', 'first', 'second', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default'],
            ['sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default', 'sheet-3-default']
        ], array_slice($sheetData['Sheet-3'], 0, 4));
    }

    /**
     * @test
     */
    public function it_will_stringify_2d_arrays()
    {
        $values = [[0, 1.01, -2], ['1', '2', '3'], ['h', 'hello', null], ['NULL', 'null', '0', NULL]];
        $this->assertSame([['0', '1.01', '-2'], ['1', '2', '3'], ['h', 'hello', null], ['NULL', 'null', '0', null]], SheetTemplateWriter::stringifyValues($values));
    }

    protected function setUp(): void
    {
        $this->templatePath = $this->getResourcePath('template.xlsx');
    }

    protected function getResourcePath($resourceName, $reType = null): ?string
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $resourceType = !empty($resourceType) ? $resourceType : $reType;
        $resourcePath = realpath($this->resourcesPath) . '/' . strtolower($resourceType) . '/' . $resourceName;
        return (file_exists($resourcePath) ? $resourcePath : null);
    }
}