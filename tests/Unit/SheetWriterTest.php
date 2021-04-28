<?php


namespace Sigmasolutions\Sheets\Tests\Unit;


use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use PHPUnit\Framework\TestCase;
use Sigmasolutions\Sheets\Exceptions\SigmaSheetException;
use Sigmasolutions\Sheets\Reader\SheetReader;
use Sigmasolutions\Sheets\Writer\SheetWriter;

class SheetWriterTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function it_initiates_xlsx_type_writer_by_default()
    {
        $writer = $this->openWriter();
        $this->assertEquals(Type::XLSX, $writer->getWriterType());
        $writer->close();
    }

    /**
     * @test
     */
    public function it_writes_to_current_sheet()
    {
        $data = [
            ['sample', 'text', 'this', 'is'],
            ['sample', 'text', 'this', 'is']
        ];
        $writer = $this->openWriter();
        $writer->write(sys_get_temp_dir() . "/Sample.xlsx", $data);
        $reader = $this->getReader();
        $fetchedData = $reader->getRows();
        $this->assertCount(2, $fetchedData);
        $this->assertEquals($data, $fetchedData);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_malformed_data_provided()
    {
        $this->expectException(SigmaSheetException::class);
        $data = [
            ['sample', 'text', 'this', 'is'],
            ['sample', new Cell("something"), 'this', 'is']
        ];
        $writer = $this->openWriter();
        $writer->write(sys_get_temp_dir() . "/Sample.xlsx", $data);
    }

    private function openWriter()
    {
        return SheetWriter::open();
    }

    private function getReader()
    {
        return SheetReader::openFile(sys_get_temp_dir() . "/Sample.xlsx");
    }
}