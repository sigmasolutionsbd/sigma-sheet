<?php

namespace Sigmasolutions\Sheets\Writer;

use Exception;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterInterface;
use Sigmasolutions\Sheets\Exceptions\SigmaSheetException;

class SheetWriter
{
    /** @var WriterInterface */
    private $writer;

    /** @var string */
    private $writerType;

    /**
     * Create a SheetWriter instance
     * 
     * @param string $writerType
     * @return SheetWriter
     * * @throws SigmaSheetException
     */
    public static function open($writerType = 'xlsx')
    {
        return (new static($writerType));
    }

    /**
     * SheetWriter constructor.
     * 
     * @param $writerType
     * @throws SigmaSheetException
     */
    public function __construct($writerType)
    {
        $this->writerType = $writerType;
        try {
            $this->writer = WriterEntityFactory::createWriter($writerType);
        } catch (Exception $exception) {
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
     * Opens the file for writing, writes all rows, closes the file - all in one go. Good for writing small data
     * 
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
        } catch (Exception $exception) {
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
