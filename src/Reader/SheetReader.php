<?php


namespace Sigmasolutions\Sheets\Reader;


use Box\Spout\Common\Exception\SpoutException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Sigmasolutions\Sheets\Exceptions\SigmaSheetException;

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

    private $readerType;

    private $isIndexBaseSheetIteration = true;

    public function __construct(string $filePath, $readerType = null)
    {
        $this->filePath = $filePath;
        $this->readerType = $readerType;
        if (is_null($readerType)) {
            $this->readerType = \strtolower(\pathinfo($filePath, PATHINFO_EXTENSION));
        }
    }

    public static function openFile($filePath)
    {
        return new static($filePath);
    }

    public static function openFileAsXLSX($filePath)
    {
        return new static($filePath, Type::XLSX);
    }

    public static function openFileAsCSV($filePath)
    {
        return new static($filePath, Type::CSV);
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
            if (!array_key_exists($index, $dataOfSheets)) {
                $dataOfSheets[$ref] = [];
            }
            $dataOfSheets[$ref] = array_merge($dataOfSheets[$ref], $chunk);
        });
        if ($noOfSelectedSheet === 1) {
            if (empty($dataOfSheets)) {
                return [];
            }
            return $dataOfSheets[array_keys($dataOfSheets)[0]];
        }
        return $dataOfSheets;
    }

    /**
     * @param callable|null $dataConsumer
     * @return array
     * @throws SigmaSheetException
     */
    public function readAsChunks(callable $dataConsumer)
    {
        try {
            $reader = ReaderFactory::createFromType($this->readerType);
            $reader->open($this->filePath);
            $originalSheets = iterator_to_array($reader->getSheetIterator());
            $filteredSheets = $this->getSelectedSheets($originalSheets);

            foreach ($filteredSheets as $sheetWithInxAndName) {
                $skip = $this->skipInitialNumberOfRows;
                $dataChunks = [];
                foreach ($sheetWithInxAndName['sheet']->getRowIterator() as $row) {
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
                        $dataChunks = [];
                    }
                }

                if (count($dataChunks) != 0) {
                    $dataConsumer($dataChunks, $sheetWithInxAndName['index'], $sheetWithInxAndName['name']);
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
     * @param $originalSheets
     * @return array
     * @throws SigmaSheetException
     */
    public function getSelectedSheets($originalSheets)
    {
        if (!$this->shouldUseHiddenSheet) {
            $originalSheets = array_values(array_filter($originalSheets, function ($sheet) {
                return $sheet->isVisible();
            }));
        }
        $originalSheetsWithIndexAndName = array_map(function ($idx, $sheet) {
            return ['name' => $sheet->getName(), 'index' => $idx, 'sheet' => $sheet];
        }, array_keys($originalSheets), $originalSheets);

        return $this->filterSelectedSheets($originalSheetsWithIndexAndName, $this->isIndexBaseSheetIteration ? $this->sheetIndexs : $this->sheetNames);
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
                return ($this->isIndexBaseSheetIteration && $ref == $sheetWithIdxAndName['index']) || (!$this->isIndexBaseSheetIteration && $ref == $sheetWithIdxAndName['name']);
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
}
