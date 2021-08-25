<?php

namespace Sigmasolutions\Sheets\Writer;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Sigmasolutions\Sheets\Exceptions\SigmaSheetException;

class SheetWriter
{
    /**
     * @var WriterEntityFactory
     */
    private $writer;
    /**
     * @var string
     */
    private $writerType;

    /**
     * SheetWriter constructor.
     * @param $writerType
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    public function __construct($writerType)
    {
        $this->writerType = $writerType;
        $this->writer = WriterEntityFactory::createWriter($writerType);
    }

    /**
     * @param $filePath
     * @param string $writerType
     * @return SheetWriter
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    public static function open($writerType = 'xlsx')
    {
        try {
            return (new SheetWriter($writerType));
        } catch (\Exception $exception) {
            throw new SigmaSheetException($exception->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getWriterType()
    {
        return $this->writerType;
    }

    /**
     * @param string $filePath
     * @param array $rows
     * @throws SigmaSheetException
     */
    public function write(string $filePath, array $rows): void
    {
        try {
            $this->writer->openToFile($filePath);
            if (is_string($rows[0])) {
                $this->writer->addRow(WriterEntityFactory::createRowFromArray($rows));
            }
            foreach ($rows as $row) {
                $this->writer->addRow(WriterEntityFactory::createRowFromArray($row));
            }
        } catch (\Exception $exception) {
            throw new SigmaSheetException($exception->getMessage());
        } finally {
            $this->close();
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        $this->writer->close();
    }
}
