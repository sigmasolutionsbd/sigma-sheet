<?php


namespace Sigmasolutions\Sheets\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sigmasolutions\Sheets\Reader\SheetReader;

class SheetTxtFileReaderTest extends TestCase
{
    private $commaSeparatedFilePath;
    private $tabSeparatedFilePath;


    /**
     * @test
     */
    public function it_reads_comma_separated_txt_file()
    {
        $data = SheetReader::openFileAsTxt($this->commaSeparatedFilePath, function ($line) {
            return array_map('trim', explode(',', $line));
        })
            ->getRows();
        $this->assertEquals([
            ["header1", "header2", "header3"],
            ["value11", "value12", "value13"],
            ["value21", "value22", "value23"],
            ["value31", "value32", "value33"],
        ], $data);
    }

    /**
     * @test
     */
    public function it_reads_comma_separated_txt_file_using_open_file()
    {
        $data = SheetReader::openFile($this->commaSeparatedFilePath)
            ->setFieldSeparator(function ($line) {
                return array_map('trim', explode(',', $line));
            })->getRows();

        $this->assertEquals([
            ["header1", "header2", "header3"],
            ["value11", "value12", "value13"],
            ["value21", "value22", "value23"],
            ["value31", "value32", "value33"],
        ], $data);
    }


    /**
     * @test
     */
    public function it_reads_comma_separated_txt_file_with_text_field_separator()
    {
        $data = SheetReader::openFileAsTxt($this->commaSeparatedFilePath, ",")->getRows();
        $this->assertEquals([
            ["header1", "header2", "header3\n"],
            ["value11", "value12", "value13\n"],
            ["value21", "value22", "value23\n"],
            ["value31", "value32", "value33\n"],
        ], $data);
    }

    /**
     * @test
     */
    public function it_reads_tab_separated_txt_file()
    {
        $data = SheetReader::openFileAsTxt($this->tabSeparatedFilePath, function ($line) {
            return array_map('trim', explode("\t", $line));
        })
            ->getRows();
        $this->assertEquals([
            ["header1", "header2", "header3"],
            ["value11", "value12", "value13"],
            ["value21", "value22", "value23"],
            ["value31", "value32", "value33"],
        ], $data);
    }

    protected function setUp(): void
    {
        $this->commaSeparatedFilePath = Utility::getResourcePath('sample1_comma_separated.txt');
        $this->tabSeparatedFilePath = Utility::getResourcePath('sample1_tab_separated.txt');
    }
}