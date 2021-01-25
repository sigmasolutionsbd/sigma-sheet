<?php declare(strict_types=1);

namespace Sigmasolutions\Sheets\Tests\Unit;


use PHPUnit\Framework\TestCase;
use Sigmasolutions\Sheets\Reader\SheetReader;
use Sigmasolutions\Sheets\Writer\SheetTemplateWriter;


class SheetWriterTest extends TestCase
{
    private $resourcesPath = 'tests/resources';

    private $templatePath;

    private $arrayData = [
        ['', 2010, 2011, 2012],
        ['Q1', 12, 15, 21, 10, 10, -100],
        ['Q2', 56, 73, 86],
        ['Q3', 52, 61, 69],
        ['Q4', 30, 32, 0, null, null, -30],
    ];

    /**
     * @test
     */
    public function it_should_write_single_sheet()
    {
        $tmpFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tmpFile)['uri'];

        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheet(
                0,
                $this->arrayData,
                "A3")
            ->save($tempFilePath);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetIndexs(0)
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);
        fclose($tmpFile);
    }

    /**
     * @test
     */
    public function it_should_write_single_sheet_by_name()
    {
        $tmpFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tmpFile)['uri'];

        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheetByName(
                "CS_LAC_KPI",
                $this->arrayData,
                "A3")
            ->save($tempFilePath);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetNames('CS_LAC_KPI')
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);
        fclose($tmpFile);
    }

    /**
     * @test
     */
    public function it_should_write_multiple_sheet()
    {
        $tmpFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tmpFile)['uri'];

        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheet(
                0,
                $this->arrayData,
                "A3")
            ->writeToSheet(
                1,
                $this->arrayData,
                "A3")
            ->writeToSheet(
                2,
                $this->arrayData,
                "A3")
            ->save($tempFilePath);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetIndexs(0)
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetIndexs(1)
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetIndexs(2)
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);

        fclose($tmpFile);
    }

    /**
     * @test
     */
    public function it_should_write_multiple_sheet_by_name()
    {
        $tmpFile = tmpfile();
        $tempFilePath = stream_get_meta_data($tmpFile)['uri'];

        SheetTemplateWriter::open($this->templatePath)
            ->writeToSheetByName(
                "CS_LAC_KPI",
                $this->arrayData,
                "A3")
            ->writeToSheetByName(
                "Cell_KPI_RAN",
                $this->arrayData,
                "A3")
            ->writeToSheetByName(
                "BSC_KPI_RAN",
                $this->arrayData,
                "A3")
            ->save($tempFilePath);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetNames("CS_LAC_KPI")
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetNames("Cell_KPI_RAN")
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);

        $data = SheetReader::openFileAsXLSX($tempFilePath)
            ->setSheetNames("BSC_KPI_RAN")
            ->skipInitialRows(2)
            ->getRows([0, 1, 2, 3, 4, 5, 6]);
        $data = array_slice($data, 0, 5);
        $this->assertEquals([['', '2010', '2011', '2012', '', '', ''], ['Q1', '12', '15', '21', '10', '10', '-100'], ['Q2', '56', '73', '86', '', '', ''], ['Q3', '52', '61', '69', '', '', ''], ['Q4', '30', '32', '0', '', '', '-30']], $data);

        fclose($tmpFile);
    }

    /**
     * @test
     */
    public function it_will_stringyfy_2d_arrays()
    {
        $values = [[0, 1.01, -2], ['1', '2', '3'], ['h', 'hello', null], ['NULL', 'null', '0', NULL]];
        $this->assertSame([['0', '1.01', '-2'], ['1', '2', '3'], ['h', 'hello', ''], ['NULL', 'null', '0', '']], SheetTemplateWriter::stringyfyValues($values));
    }

    protected function setUp(): void
    {
        $this->templatePath = $this->getResourcePath('BSC.xlsx');
    }

    protected function getResourcePath($resourceName, $reType = null)
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $resourceType = !empty($resourceType) ? $resourceType : $reType;
        $resourcePath = realpath($this->resourcesPath) . '/' . strtolower($resourceType) . '/' . $resourceName;
        return (file_exists($resourcePath) ? $resourcePath : null);
    }
}