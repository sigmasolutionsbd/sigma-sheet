<?php

namespace Sigmasolutions\Sheets\Reader\Txt;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\ReaderAbstract;
use Sigmasolutions\Sheets\Reader\Txt\Creator\InternalEntityFactory;
use Sigmasolutions\Sheets\Reader\Txt\Manager\Options;

/**
 * Class Reader
 * This class provides support to read data from a TXT file.
 */
class Reader extends ReaderAbstract
{
    /** @var resource Pointer to the file to be written */
    protected $filePointer;

    /** @var SheetIterator To iterator over the TXT unique "sheet" */
    protected $sheetIterator;

    /** @var string Original value for the "auto_detect_line_endings" INI value */
    protected $originalAutoDetectLineEndings;

    /**
     * Sets the field delimiter for the TXT.
     * Needs to be called before opening the reader.
     *
     * @param callable | string $fieldSeparator callback($row) or Character that delimits fields
     * @return Reader
     */
    public function setTxtFieldSeparator($fieldSeparator)
    {
        $this->optionsManager->setOption(Options::TXT_FIELD_SEPARATOR, $fieldSeparator);
        return $this;
    }

    /**
     * Returns whether stream wrappers are supported
     *
     * @return bool
     */
    protected function doesSupportStreamWrapper()
    {
        return true;
    }

    /**
     * Opens the file at the given path to make it ready to be read.
     * If setEncoding() was not called, it assumes that the file is encoded in UTF-8.
     *
     * @param string $filePath Path of the TXT file to be read
     * @return void
     * @throws IOException
     */
    protected function openReader($filePath)
    {
        $this->originalAutoDetectLineEndings = \ini_get('auto_detect_line_endings');
        \ini_set('auto_detect_line_endings', '1');

        $this->filePointer = $this->globalFunctionsHelper->fopen($filePath, 'r');
        if (!$this->filePointer) {
            throw new IOException("Could not open file $filePath for reading.");
        }

        /** @var InternalEntityFactory $entityFactory */
        $entityFactory = $this->entityFactory;

        $this->sheetIterator = $entityFactory->createSheetIterator(
            $this->filePointer,
            $this->optionsManager,
            $this->globalFunctionsHelper
        );
    }

    /**
     * Returns an iterator to iterate over sheets.
     *
     * @return SheetIterator To iterate over sheets
     */
    protected function getConcreteSheetIterator()
    {
        return $this->sheetIterator;
    }

    /**
     * Closes the reader. To be used after reading the file.
     *
     * @return void
     */
    protected function closeReader()
    {
        if ($this->filePointer) {
            $this->globalFunctionsHelper->fclose($this->filePointer);
        }

        \ini_set('auto_detect_line_endings', $this->originalAutoDetectLineEndings);
    }
}
