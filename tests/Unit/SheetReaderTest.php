<?php declare(strict_types=1);

namespace Sigmasolutions\Sheets\Tests\Unit;


use PHPUnit\Framework\TestCase;
use Sigmasolutions\Sheets\Exceptions\SigmaSheetException;
use Sigmasolutions\Sheets\Reader\SheetReader;


class SheetReaderTest extends TestCase
{
    private $sheetValues = [
        [["s1header1", "s1header2", "s1header3"], ["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]],
        [["s2header1", "s2header2", "s2header3"], ["s2va2", "s2vb2", "s2vc2"], ["s2va3", "s2vb3", "s2vc3"], ["s2va4", "s2vb4", "s2vc4"]],
        [["s3header1", "s3header2", "s3header3"], ["s3va2", "s3vb2", "s3vc2"], ["s3va3", "s3vb3", "s3vc3"], ["s3va4", "s3vb4", "s3vc4"]]
    ];

    private $multiSheetPath;
    private $multiSheetWithHiddenSheetPath;
    private $multiSheetXLSXWithoutExtensionPath;
    private $multiSheetCSVWithoutExtensionPath;

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_support_open_file_static_method()
    {
        $data = SheetReader::openFile($this->multiSheetPath)->setSheetIndexs([0])->getRows();
        $this->assertEquals([["s1header1", "s1header2", "s1header3"], ["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]], $data);
        $data = SheetReader::openFile($this->multiSheetPath)->setSheetIndexs(1)->getRows();
        $this->assertEquals([["s2header1", "s2header2", "s2header3"], ["s2va2", "s2vb2", "s2vc2"], ["s2va3", "s2vb3", "s2vc3"], ["s2va4", "s2vb4", "s2vc4"]], $data);

    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_throw_sigma_sheet_exception_if_file_not_found()
    {
        $this->expectExceptionObject(new SigmaSheetException('Could not open hello.xlsx for reading! File does not exist.'));
        SheetReader::openFile("hello.xlsx")->getRows();
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_throw_sigma_sheet_exception_if_file_is_not_supported()
    {
        $this->expectExceptionObject(new SigmaSheetException('No readers supporting the given type: php'));
        SheetReader::openFile("hello.php")->getRows();
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_support_hidden_sheets()
    {
        // Hidden sheet index starts at 1
        $data = SheetReader::openFile($this->multiSheetWithHiddenSheetPath)->useHiddenSheet()->setSheetIndexs(1)->getRows();
        $this->assertEquals([['hidden1']], $data);

        $data = SheetReader::openFile($this->multiSheetWithHiddenSheetPath)->setSheetIndexs(0)->getRows();
        $this->assertEquals([["s1header1", "s1header2", "s1header3"], ["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]], $data);
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_throw_exception_if_sheet_index_not_found()
    {
        $this->expectExceptionObject(new SigmaSheetException('undefined sheet 10'));
        SheetReader::openFile($this->multiSheetPath)->setSheetIndexs(10)->getRows();
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_throw_exception_if_sheet_name_not_found()
    {
        $this->expectExceptionObject(new SigmaSheetException('undefined sheet sheet-10'));
        SheetReader::openFile($this->multiSheetPath)->setSheetNames('sheet-10')->getRows();
    }


    /**
     * @test
     */
    public function set_sheet_index_should_works_with_single_and_array()
    {
        $sheetReader = new SheetReader($this->multiSheetPath);
        $this->assertEquals([0], $sheetReader->setSheetIndexs(0)->getSheetIndexs());
        $this->assertEquals([0, 1, 3], $sheetReader->setSheetIndexs([0, 1, 3])->getSheetIndexs());
    }

    /**
     * @test
     */
    public function set_sheet_name_should_works_with_single_and_array()
    {
        $sheetReader = new SheetReader($this->multiSheetPath);
        $this->assertEquals(['sheet1'], $sheetReader->setSheetNames('sheet1')->getSheetNames());
        $this->assertEquals(['sheet1'], $sheetReader->setSheetNames('sheet1 ')->getSheetNames());
        $this->assertEquals(['sheet1', 'sheet3'], $sheetReader->setSheetNames(['sheet1', 'sheet3'])->getSheetNames());
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function that_read_as_chunks_should_works_without_any_configuration_for_xlsx()
    {
        SheetReader::openFile($this->multiSheetPath)->readAsChunks(function ($data) {
            $this->assertEquals($this->sheetValues[0], $data);
        });
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function that_skip_works_for_read_as_chunks()
    {
        $sheetReader = new SheetReader($this->multiSheetPath);
        $sheetReader->skipInitialRows(1)->readAsChunks(function ($data) {
            $this->assertEquals(array_slice($this->sheetValues[0], 1), $data);
        });
        $sheetReader->skipHeader()->readAsChunks(function ($data) {
            $this->assertEquals(array_slice($this->sheetValues[0], 1), $data);
        });
        $sheetReader->skipInitialRows(2)->readAsChunks(function ($data) {
            $this->assertEquals(array_slice($this->sheetValues[0], 2), $data);
        });
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function that_set_sheet_index_works_for_read_as_chunks()
    {
        $sheetData = [];
        SheetReader::openFile($this->multiSheetPath)->setSheetIndexs(0)->readAsChunks(function ($data, $sheetIndex, $sheetName) use (&$sheetData) {
            $sheetData = array_merge($sheetData, $data);
        });
        $this->assertEquals($this->sheetValues[0], $sheetData);

        $sheetData = [];
        SheetReader::openFile($this->multiSheetPath)->setSheetIndexs(2)->readAsChunks(function ($data) use (&$sheetData) {
            $sheetData = array_merge($sheetData, $data);
        });
        $this->assertEquals($this->sheetValues[2], $sheetData);

        $multiSheetData = [];
        SheetReader::openFile($this->multiSheetPath)->setSheetIndexs([0, 1, 2])->readAsChunks(function ($data, $sheetIndex, $sheetName) use (&$multiSheetData) {
            $multiSheetData[$sheetIndex] = $data;
        });

        $this->assertEquals($this->sheetValues[0], $multiSheetData[0]);
        $this->assertEquals($this->sheetValues[1], $multiSheetData[1]);
        $this->assertEquals($this->sheetValues[2], $multiSheetData[2]);
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function that_set_sheet_names_works_for_read_as_chunks()
    {
        $sheetData = [];
        SheetReader::openFile($this->multiSheetPath)->setSheetNames('Sheet-1')->readAsChunks(function ($data) use (&$sheetData) {
            $sheetData = array_merge($sheetData, $data);
        });
        $this->assertEquals($this->sheetValues[0], $sheetData);
        $sheetData = [];
        SheetReader::openFile($this->multiSheetPath)->setSheetNames('Sheet-3')->readAsChunks(function ($data) use (&$sheetData) {
            $sheetData = array_merge($sheetData, $data);
        });
        $this->assertEquals($this->sheetValues[2], $sheetData);

        $multiSheetData = [];
        SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1', 'Sheet-2', 'Sheet-3'])->readAsChunks(function ($data, $sheetIndex, $sheetName) use (&$multiSheetData) {
            $multiSheetData[$sheetName] = $data;
        });
        $this->assertEquals($this->sheetValues[0], $multiSheetData['Sheet-1']);
        $this->assertEquals($this->sheetValues[1], $multiSheetData['Sheet-2']);
        $this->assertEquals($this->sheetValues[2], $multiSheetData['Sheet-3']);
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_is_ok_to_use_add_mapper_and_should_return_mapped_row()
    {
        SheetReader::openFile($this->multiSheetPath)->addMapper(function ($cells) {
            return join(',', $cells);
        })->readAsChunks(function ($data) {
            $this->assertEquals(["s1header1,s1header2,s1header3", "s1va2,s1vb2,s1vc2", "s1va3,s1vb3,s1vc3", "s1va4,s1vb4,s1vc4"], $data);
        });
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_is_ok_to_use_add_filter_and_should_filter_row()
    {
        SheetReader::openFile($this->multiSheetPath)->addFilter(function ($cells) {
            return strpos(join(',', $cells), 'header') === false;
        })->readAsChunks(function ($data) {
            $this->assertEquals([["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]], $data);
        });
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_be_ok_to_use_multiple_add_mapper_and_add_filter()
    {
        SheetReader::openFile($this->multiSheetPath)->addFilter(function ($cells) {
            return strpos(join(',', $cells), 'header') === false;
        })->addMapper(function ($cells) {
            return join(',', $cells);
        })->addFilter(function ($data) {
            return strpos($data, '4,s1vb4') === false;
        })->addMapper(function ($data) {
            return explode(',', $data);
        })
            ->readAsChunks(function ($data) {
                $this->assertEquals([["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"]], $data);
            });
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_set_chunk_size_and_should_get_chunk_sized_data()
    {
        $sheetReader = new SheetReader($this->multiSheetPath);
        $sheetReader->chunkSize(2)
            ->readAsChunks(function ($data) {
                $this->assertEquals(2, count($data));
            });
        $sheetReader->chunkSize(1)
            ->readAsChunks(function ($data) {
                $this->assertEquals(1, count($data));
            });
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function that_get_rows_works_correctly()
    {
        $sheetReader = SheetReader::openFile($this->multiSheetPath);
        $this->assertEquals([["s1header1", "s1header2", "s1header3"], ["s1va2", "s1vb2", "s1vc2"], ["s1va3", "s1vb3", "s1vc3"], ["s1va4", "s1vb4", "s1vc4"]], $sheetReader->getRows());

        # Get rows with selected column
        $this->assertEquals([["s1header1", "s1header2", "s1header3", "s1header1"], ["s1va2", "s1vb2", "s1vc2", "s1va2"], ["s1va3", "s1vb3", "s1vc3", "s1va3"], ["s1va4", "s1vb4", "s1vc4", "s1va4"]],
            SheetReader::openFile($this->multiSheetPath)->getRows([0, 1, 2, 0]));
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_be_possible_to_read_multiple_sheets_with_get_rows_using_indexes()
    {
        $data = SheetReader::openFile($this->multiSheetPath)->setSheetIndexs([2, 1, 0])->getRows();
        $this->assertEquals($this->sheetValues[0], $data[0]);
        $this->assertEquals($this->sheetValues[1], $data[1]);
        $this->assertEquals($this->sheetValues[2], $data[2]);
    }

    /**
     * @test
     * @throws SigmaSheetException
     */
    public function it_should_be_possible_to_read_multiple_sheets_with_get_rows_using_sheet_names()
    {
        $data = SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1', 'Sheet-2', 'Sheet-3'])->getRows();
        $this->assertEquals($this->sheetValues[0], $data['Sheet-1']);
        $this->assertEquals($this->sheetValues[1], $data['Sheet-2']);
        $this->assertEquals($this->sheetValues[2], $data['Sheet-3']);
    }

    /**
     * @test
     */
    public function it_can_read_first_n_rows()
    {
       $this->assertCount(1, SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1'])->getFirstNRows(1));
        $this->assertEquals($this->sheetValues[0][0], SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1'])->getFirstNRows(1)[0]);

        $this->assertCount(2, SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1'])->getFirstNRows(2));
        $this->assertEquals($this->sheetValues[0][1], SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1'])->getFirstNRows(2)[1]);
        $this->assertCount(3, SheetReader::openFile($this->multiSheetPath)->setSheetNames(['Sheet-1'])->getFirstNRows(3));
    }
    /**
     * @test
     */
    public function it_allows_open_file_as_xlsx()
    {
        $data = SheetReader::openFileAsXLSX($this->multiSheetXLSXWithoutExtensionPath)->setSheetNames(['Sheet-1', 'Sheet-2', 'Sheet-3'])->getRows();

        $this->assertEquals($this->sheetValues[0], $data['Sheet-1']);
        $this->assertEquals($this->sheetValues[1], $data['Sheet-2']);
        $this->assertEquals($this->sheetValues[2], $data['Sheet-3']);
    }

    /**
     * @test
     */
    public function it_allows_open_file_as_csv()
    {
        $data = SheetReader::openFileAsCSV($this->multiSheetCSVWithoutExtensionPath)->getRows();
        $this->assertEquals($this->sheetValues[0], $data);
    }

    protected function setUp(): void
    {
        $this->multiSheetPath = Utility::getResourcePath('multi_sheet.xlsx');
        $this->multiSheetXLSXWithoutExtensionPath = Utility::getResourcePath('multi_sheet_xlsx_without_extension', 'xlsx');
        $this->multiSheetCSVWithoutExtensionPath = Utility::getResourcePath('multi_sheet_csv_without_extension', 'csv');
        $this->multiSheetWithHiddenSheetPath = Utility::getResourcePath('multi_sheet_invalid_index.xlsx');
    }
}