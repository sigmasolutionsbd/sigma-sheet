<?php

namespace Sigmasolutions\Sheets\Reader;

use Box\Spout\Common\Exception\SpoutException;
use Sigmasolutions\Sheets\Exceptions\SigmaSheetException;
use Sigmasolutions\Sheets\Reader\Creator\ReaderFactory;
use Sigmasolutions\Sheets\Reader\Creator\ReaderType;

class SheetReader
{
    private const ACTION_TYPE_FILTER = 'filter';
    private const ACTION_TYPE_MAPPER = 'mapper';
    private $filePath;
    private $skipInitialNumberOfRows = 0;
    private $chunkSize = 500;
    private $shouldRemoveEmpty = true;
    private $sheetNames = null;
    private $sheetIndexs = [0];
    private $shouldUseHiddenSheet = false;
    private $actions = [];
    private $fieldSeparator;

    private $readerType;

    private $isIndexBaseSheetIteration = true;

    public function __construct(string $filePath, $readerType = null, $fieldSeparator = null)
    {
        $this->filePath = $filePath;
        $this->readerType = $readerType;
        $this->setFieldSeparator($fieldSeparator);

        if (is_null($readerType)) {
            $this->readerType = \strtolower(\pathinfo($filePath, PATHINFO_EXTENSION));
        }
    }

    public static function openWithType(string $filePath, string $fileType): SheetReader
    {
        return new static($filePath, $fileType);
    }

    /**
     * @param $filePath
     * @return static
     */
    public static function openFile($filePath): SheetReader
    {
        return new static($filePath);
    }

    /**
     * @param $filePath
     * @return static
     */
    public static function openFileAsXLSX($filePath): SheetReader
    {
        return new static($filePath, ReaderType::XLSX);
    }

    /**
     * @param $filePath
     * @return static
     */
    public static function openFileAsCSV($filePath): SheetReader
    {
        return new static($filePath, ReaderType::CSV);
    }

    /**
     * @param $filePath
     * @param callable | string $fieldSeparator callback($row) or Character that delimits fields
     * @return SheetReader
     */
    public static function openFileAsTxt($filePath, $fieldSeparator): SheetReader
    {
        return new static($filePath, ReaderType::TXT, $fieldSeparator);
    }

    /**
     * @param callable | string $fieldSeparator callback($row) or Character that delimits fields
     * @return SheetReader
     */
    public function setFieldSeparator($fieldSeparator): SheetReader
    {
        $this->fieldSeparator = $fieldSeparator;
        return $this;
    }

    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    public function getSkipInitialRows()
    {
        return $this->skipInitialNumberOfRows;
    }

    public function skipHeader(): SheetReader
    {
        $this->skipInitialRows(1);
        return $this;
    }

    /**
     * @param int $skipInitialNumberOfRows Skip Initial Number of Rows
     * @return SheetReader
     */
    public function skipInitialRows(int $skipInitialNumberOfRows): SheetReader
    {
        if ($skipInitialNumberOfRows < 0) {
            $this->skipInitialNumberOfRows = 0;
        }
        $this->skipInitialNumberOfRows = $skipInitialNumberOfRows;
        return $this;
    }

    /**
     * @param array|null $selectedCols
     * @return array
     * @throws SigmaSheetException
     */
    public function getRows(?array $selectedCols = null): array
    {
        if (is_null($selectedCols) || count($selectedCols) == 0) {
            $selectedCols = ['*'];
        }

        $noOfSelectedSheet = $this->isIndexBaseSheetIteration ? count($this->sheetIndexs) : count($this->sheetNames);

        $dataOfSheets = [];
        $this->addMapper(function ($cells) use ($selectedCols) {
            if ($selectedCols[0] === '*') {
                return $cells;
            }
            return array_map(function ($selectedCol) use ($cells) {
                if (!array_key_exists($selectedCol, $cells)) {
                    throw new SigmaSheetException('undefined column number ' . $selectedCol);
                }
                return trim($cells[$selectedCol]);
            }, $selectedCols);
        })->readAsChunks(function ($chunk, $index, $sheetName) use (&$dataOfSheets) {
            $ref = $this->isIndexBaseSheetIteration ? $index : $sheetName;
            if (!array_key_exists($ref, $dataOfSheets)) {
                $dataOfSheets[$ref] = [];
            }
            $dataOfSheets[$ref] = array_merge($dataOfSheets[$ref], $chunk);
        });
        if ($noOfSelectedSheet === 1) {
            if (empty($dataOfSheets)) {
                return [[]];
            }
            return $dataOfSheets[array_keys($dataOfSheets)[0]];
        }
        return $dataOfSheets;
    }

    /**
     * @param callable $dataConsumer
     * @param int $noOfChunks Number of chunks 0 = All
     * @throws SigmaSheetException
     */
    public function readAsChunks(callable $dataConsumer, $noOfChunks = 0)
    {
        try {
            $reader = ReaderFactory::createFromType($this->readerType);
            if (!empty($this->fieldSeparator)) {
                $reader->setTxtFieldSeparator($this->fieldSeparator);
            }
            $reader->open($this->filePath);
            $originalSheets = iterator_to_array($reader->getSheetIterator());
            $filteredSheets = $this->getSelectedSheets($originalSheets);

            $chunkReturned = 0;

            foreach ($filteredSheets as $sheetWithInxAndName) {
                $skip = $this->skipInitialNumberOfRows;
                $dataChunks = [];
                foreach ($sheetWithInxAndName['sheet']->getRowIterator() as $row) {
                    if ($noOfChunks != 0 && $chunkReturned >= $noOfChunks) {
                        return;
                    }
                    if ($this->shouldRemoveEmpty && empty($row)) {
                        continue;
                    }

                    if ($skip > 0) {
                        $skip--;
                        continue;
                    }

                    $data = $this->applyActions($row);
                    if ($data === false) {
                        continue;
                    }
                    $dataChunks[] = $data;
                    if (count($dataChunks) % $this->chunkSize == 0) {
                        $dataConsumer($dataChunks, $sheetWithInxAndName['index'], $sheetWithInxAndName['name']);
                        $chunkReturned++;
                        $dataChunks = [];
                    }
                }

                if (count($dataChunks) != 0) {
                    $dataConsumer($dataChunks, $sheetWithInxAndName['index'], $sheetWithInxAndName['name']);
                    $chunkReturned++;
                }
            }
        } catch (SpoutException $spoutException) {
            throw new SigmaSheetException(($spoutException->getMessage()));
        } finally {
            if (isset($reader)) {
                $reader->close();
            }
        }
    }

    /**
     * @param int $noOfRows
     * @return array
     * @throws SigmaSheetException
     */
    public function getFirstNRows(int $noOfRows): array
    {
        $firstNRows = [];
        $this->chunkSize(1)
            ->readAsChunks(function ($row) use (&$firstNRows) {
                $firstNRows = array_merge($firstNRows, $row);
            }, $noOfRows);
        return $firstNRows;
    }

    /**
     * @param $originalSheets
     * @return array
     * @throws SigmaSheetException
     */
    public function getSelectedSheets($originalSheets)
    {
        if (!$this->shouldUseHiddenSheet) {
            $originalSheets = array_values(
                array_filter($originalSheets, function ($sheet) {
                    return $sheet->isVisible();
                })
            );
        }
        $originalSheetsWithIndexAndName = array_map(function ($idx, $sheet) {
            return ['name' => $sheet->getName(), 'index' => $idx, 'sheet' => $sheet];
        }, array_keys($originalSheets), $originalSheets);

        return $this->filterSelectedSheets(
            $originalSheetsWithIndexAndName,
            $this->isIndexBaseSheetIteration ? $this->sheetIndexs : $this->sheetNames
        );
    }

    /**
     * @param $originalSheets
     * @param $sheetRefs
     * @return array
     * @throws SigmaSheetException
     */
    private function filterSelectedSheets($originalSheets, $sheetRefs)
    {
        $filteredSheets = [];
        foreach ($sheetRefs as $ref) {
            $founds = array_filter($originalSheets, function ($sheetWithIdxAndName) use ($ref) {
                return ($this->isIndexBaseSheetIteration && $ref == $sheetWithIdxAndName['index'])
                    || (!$this->isIndexBaseSheetIteration && $ref == $sheetWithIdxAndName['name']);
            });
            if (empty($founds) || count($founds) !== 1) {
                throw new SigmaSheetException('undefined sheet ' . $ref);
            }
            $filteredSheets[] = array_pop($founds);
        }
        return $filteredSheets;
    }

    private function applyActions($row)
    {
        $cells = $row->toArray();
        foreach ($this->actions as $action) {
            switch ($action['type']) {
                case static::ACTION_TYPE_MAPPER:
                    $cells = $action['action']($cells);
                    break;
                case static::ACTION_TYPE_FILTER:
                    $isFiltered = boolval($action['action']($cells));
                    if (!$isFiltered) {
                        return false;
                    }
                    break;
            }
        }
        return $cells;
    }

    public function addMapper(callable $mapper): SheetReader
    {
        $this->actions[] = ['type' => static::ACTION_TYPE_MAPPER, 'action' => $mapper];
        return $this;
    }

    /**
     * @param string | int $chunkSize Set Bulk Read chunk size
     * @return SheetReader
     */
    public function chunkSize($chunkSize): SheetReader
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Keep empty rows
     * @return $this
     */
    public function keepEmptyRow()
    {
        $this->shouldRemoveEmpty = false;
        return $this;
    }

    public function shouldKeepEmptyRow()
    {
        return !$this->shouldRemoveEmpty;
    }

    public function getSheetNames()
    {
        return $this->sheetNames;
    }

    public function setSheetNames($sheetNames): SheetReader
    {
        $this->isIndexBaseSheetIteration = false;
        $this->sheetNames = array_map(function ($value) {
            return trim($value);
        }, is_array($sheetNames) ? $sheetNames : [$sheetNames]);
        return $this;
    }

    public function getSheetIndexs()
    {
        return $this->sheetIndexs;
    }

    public function setSheetIndexs($sheetIndexs): SheetReader
    {
        $this->isIndexBaseSheetIteration = true;
        $this->sheetIndexs = is_array($sheetIndexs) ? $sheetIndexs : [$sheetIndexs];
        return $this;
    }

    public function useHiddenSheet(): SheetReader
    {
        $this->shouldUseHiddenSheet = true;
        return $this;
    }

    public function addFilter(callable $filter): SheetReader
    {
        $this->actions[] = ['type' => static::ACTION_TYPE_FILTER, 'action' => $filter];
        return $this;
    }

    public function getOriginalSheetNames()
    {
        $reader = ReaderFactory::createFromType($this->readerType);
        $reader->open($this->filePath);
        $sheetNames = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetNames[] = $sheet->getName();
        }
        return $sheetNames;
    }
}
