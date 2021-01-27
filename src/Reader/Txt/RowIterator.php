<?php

namespace Sigmasolutions\Sheets\Reader\Txt;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Helper\GlobalFunctionsHelper;
use Box\Spout\Common\Manager\OptionsManagerInterface;
use Box\Spout\Reader\IteratorInterface;
use Sigmasolutions\Sheets\Reader\Txt\Creator\InternalEntityFactory;
use Sigmasolutions\Sheets\Reader\Txt\Manager\Options;

/**
 * Class RowIterator
 * Iterate over Log rows.
 */
class RowIterator implements IteratorInterface
{
    /**
     * /** @var resource Pointer to the Txt file to read
     */
    protected $filePointer;

    /** @var int Number of read rows */
    protected $numReadRows = 0;

    /** @var Row|null Buffer used to store the current row, while checking if there are more rows to read */
    protected $rowBuffer;

    /** @var bool Indicates whether all rows have been read */
    protected $hasReachedEndOfFile = false;

    /** @var bool Whether empty rows should be returned or skipped */
    protected $shouldPreserveEmptyRows;

    /** @var Creator\InternalEntityFactory Factory to create entities */
    protected $entityFactory;
    /** @var GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    protected $txtFieldSeparator;

    /**
     * @param resource $filePointer Pointer to the TXT file to read
     * @param OptionsManagerInterface $optionsManager
     * @param InternalEntityFactory $entityFactory
     * @param GlobalFunctionsHelper $globalFunctionsHelper
     */
    public function __construct(
        $filePointer,
        OptionsManagerInterface $optionsManager,
        InternalEntityFactory $entityFactory,
        GlobalFunctionsHelper $globalFunctionsHelper
    )
    {
        $this->filePointer = $filePointer;


        $this->shouldPreserveEmptyRows = $optionsManager->getOption(Options::SHOULD_PRESERVE_EMPTY_ROWS);
        $this->txtFieldSeparator = $optionsManager->getOption(Options::TXT_FIELD_SEPARATOR);

        $this->entityFactory = $entityFactory;
        $this->globalFunctionsHelper = $globalFunctionsHelper;
    }

    /**
     * Rewind the Iterator to the first element
     * @see http://php.net/manual/en/iterator.rewind.php
     *
     * @return void
     */
    public function rewind()
    {
        $this->globalFunctionsHelper->fseek($this->filePointer, 0);
        $this->numReadRows = 0;
        $this->rowBuffer = null;

        $this->next();
    }

    /**
     * Checks if current position is valid
     * @see http://php.net/manual/en/iterator.valid.php
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->filePointer && !$this->hasReachedEndOfFile);
    }

    /**
     * Move forward to next element. Reads data for the next unprocessed row.
     * @see http://php.net/manual/en/iterator.next.php
     *
     * @return void
     */
    public function next()
    {
        $this->hasReachedEndOfFile = $this->globalFunctionsHelper->feof($this->filePointer);

        if (!$this->hasReachedEndOfFile) {
            $this->readDataForNextRow();
        }
    }

    /**
     * @return void
     */
    protected function readDataForNextRow()
    {
        do {
            $rowData = $this->getNextRowData();
        } while ($this->shouldReadNextRow($rowData));

        if ($rowData !== false) {
            // str_replace will replace NULL values by empty strings
            $rowDataBufferAsArray = \str_replace(null, null, $rowData);
            $this->rowBuffer = $this->entityFactory->createRowFromArray($rowDataBufferAsArray);
            $this->numReadRows++;
        } else {
            // If we reach this point, it means end of file was reached.
            // This happens when the last lines are empty lines.
            $this->hasReachedEndOfFile = true;
        }
    }

    /**
     * @param array|bool $currentRowData
     * @return bool Whether the data for the current row can be returned or if we need to keep reading
     */
    protected function shouldReadNextRow($currentRowData)
    {

        $hasSuccessfullyFetchedRowData = ($currentRowData !== false);
        $hasNowReachedEndOfFile = $this->globalFunctionsHelper->feof($this->filePointer);
        $isEmptyLine = $this->isEmptyLine($currentRowData);

        return (
            (!$hasSuccessfullyFetchedRowData && !$hasNowReachedEndOfFile) ||
            (!$this->shouldPreserveEmptyRows && $isEmptyLine)
        );
    }

    /**
     * @param $rowData
     * @return array|bool
     */
    private function explodeRowData($rowData)
    {
        if (is_callable($this->txtFieldSeparator)) {
            return ($this->txtFieldSeparator)($rowData);
        }
        if ($this->txtFieldSeparator == ' ') {
            $rowData = trim(preg_replace('!\s+!', ' ', $rowData));
        }
        return explode($this->txtFieldSeparator, $rowData);
    }

    private function sanitizeRow($rowData)
    {
        return str_replace("\r", '', $rowData);
    }

    private function fgetsFullLine($handle)
    {
        return \fgets($handle);
    }

    /**
     * Returns the next row
     * @return array|false The row for the current file pointer, encoded in UTF-8 or FALSE if nothing to read
     */
    protected function getNextRowData()
    {
        $rowData = $this->fgetsFullLine($this->filePointer);
        if ($rowData === false) {
            return false;
        }
        $rowData = $this->explodeRowData($this->sanitizeRow($rowData));
        if ($rowData === false) {
            return false;
        }
        return $rowData;
    }

    /**
     * @param array|bool $lineData Array containing the cells value for the line
     * @return bool Whether the given line is empty
     */
    protected function isEmptyLine($lineData)
    {
        return (\is_array($lineData) && \count($lineData) === 1 && $lineData[0] === null);
    }


    /**
     * Return the current element from the buffer
     * @see http://php.net/manual/en/iterator.current.php
     *
     * @return Row|null
     */
    public function current()
    {
        return $this->rowBuffer;
    }

    /**
     * Return the key of the current element
     * @see http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key()
    {
        return $this->numReadRows;
    }

    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end()
    {
        // do nothing
    }
}
