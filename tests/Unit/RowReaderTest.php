<?php declare(strict_types=1);

namespace Sigmasolutions\Sheets\Tests\Unit;


use PHPUnit\Framework\TestCase;
use Sigmasolutions\Sheets\Reader\SheetReader;


class RowReaderTest extends TestCase
{
    private $resourcesPath = 'tests/resources';

    private $sheetValues = [
        [["s1header1", "s1header2", "s1header3"], ["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]],
        [["s2header1", "s2header2", "s2header3"], ["s2va2", "s2vb2", "s2vc2"], ["s2va3", "s2vb3", "s2vc3"], ["s2va4", "s2vb4", "s2vc4"]],
        [["s3header1", "s3header2", "s3header3"], ["s3va2", "s3vb2", "s3vc2"], ["s3va3", "s3vb3", "s3vc3"], ["s3va4", "s3vb4", "s3vc4"]]
    ];

    /**
     * @test
     */
    public function set_sheet_index_should_works_with_single_and_array()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $this->assertEquals([0], $rowReader->setSheetIndexs(0)->getSheetIndexs());
        $this->assertEquals([0, 1, 3], $rowReader->setSheetIndexs([0, 1, 3])->getSheetIndexs());
    }

    protected function getResourcePath($resourceName)
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $resourcePath = realpath($this->resourcesPath) . '/' . strtolower($resourceType) . '/' . $resourceName;
        return (file_exists($resourcePath) ? $resourcePath : null);
    }

    /**
     * @test
     */
    public function set_sheet_name_should_works_with_single_and_array()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $this->assertEquals(['sheet1'], $rowReader->setSheetNames('sheet1')->getSheetNames());
        $this->assertEquals(['sheet1'], $rowReader->setSheetNames('sheet1 ')->getSheetNames());
        $this->assertEquals(['sheet1', 'sheet3'], $rowReader->setSheetNames(['sheet1', 'sheet3'])->getSheetNames());
    }

    /**
     * @test
     */
    public function that_read_as_chunks_should_works_without_any_configuration_for_xlsx()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->readAsChunks(function ($data) {
            $this->assertEquals($this->sheetValues[0], $data);
        });
    }

    /**
     * @test
     */
    public function that_skip_works_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->skip(1)->readAsChunks(function ($data) {
            $this->assertEquals(array_slice($this->sheetValues[0], 1), $data);
        });
        $rowReader->skip(2)->readAsChunks(function ($data) {
            $this->assertEquals(array_slice($this->sheetValues[0], 2), $data);
        });
    }

    /**
     * @test
     */
    public function that_set_sheet_index_works_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->setSheetIndexs(0)->readAsChunks(function ($data) {
            $this->assertEquals($this->sheetValues[0], $data);
        });
        $rowReader->setSheetIndexs(2)->readAsChunks(function ($data) {
            $this->assertEquals($this->sheetValues[2], $data);
        });
        $rowReader->setSheetIndexs([0, 1, 2])->readAsChunks(function ($data) {
            $this->assertEquals(array_merge($this->sheetValues[0], $this->sheetValues[1], $this->sheetValues[2]), $data);
        });
    }

    /**
     * @test
     */
    public function that_set_sheet_names_works_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->setSheetNames('Sheet-1')->readAsChunks(function ($data) {
            $this->assertEquals($this->sheetValues[0], $data);
        });
        $rowReader->setSheetNames('Sheet-3')->readAsChunks(function ($data) {
            $this->assertEquals($this->sheetValues[2], $data);
        });
        $rowReader->setSheetNames(['Sheet-1', 'Sheet-2', 'Sheet-3'])->readAsChunks(function ($data) {
            $this->assertEquals(array_merge($this->sheetValues[0], $this->sheetValues[1], $this->sheetValues[2]), $data);
        });
    }

    /**
     * @test
     */
    public function that_set_mapper_works_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->setMapper(function ($cells) {
            return join(',', $cells);
        })->readAsChunks(function ($data) {
            $this->assertEquals(["s1header1,s1header2,s1header3", "s1va2,s1vb2,s1vc2", "s1va3,s1vb3,s1vc3", "s1va4,s1vb4,s1vc4"], $data);
        });
    }

    /**
     * @test
     */
    public function that_set_filter_works_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->setFilter(function ($cells) {
            return strpos(join(',', $cells), 'header') === false;
        })->readAsChunks(function ($data) {
            $this->assertEquals([["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]], $data);
        });
    }

    /**
     * @test
     */
    public function that_set_chunk_size_works_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->chunkSize(2)
            ->readAsChunks(function ($data) {
                $this->assertEquals(2, count($data));
            });
        $rowReader->chunkSize(1)
            ->readAsChunks(function ($data) {
                $this->assertEquals(1, count($data));
            });
    }

    /**
     * @test
     */
    public function that_set_chunk_size_all_should_return_all_rows_for_read_as_chunks()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $rowReader->chunkSize('all')
            ->readAsChunks(function ($data) {
                $this->assertEquals(4, count($data));
            });
    }


    /**
     * @test
     */
    public function that_get_rows_works_correctly()
    {
        $rowReader = new SheetReader($this->getResourcePath('multi_sheet.xlsx'));
        $this->assertEquals([["s1header1", "s1header2", "s1header3"], ["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]], $rowReader->getRows());

        # Get rows with selected column
        $this->assertEquals([["s1header1", "s1header2", "s1header3", "s1header1"], ["s1va2", "s1vb2", "s1vc2", "s1va2"], ["s1va3", "s1vb3", "s1vc3", "s1va3"], ["s1va4", "s1vb4", "s1vc4", "s1va4"]],
            $rowReader->getRows([0, 1, 2, 0]));
    }
}