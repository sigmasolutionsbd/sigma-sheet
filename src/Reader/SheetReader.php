<?php


namespace Sigmasolutions\Sheets\Reader;


use Box\Spout\Common\Exception\SpoutException;
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
    private $readAsChunkCallbackSupported = true;
    private $actions = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
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
        $this->readAsChunkCallbackSupported = false;
        if (is_null($selectedCols) || count($selectedCols) == 0) {
            $selectedCols = ['*'];
        }

        return $this->addMapper(function ($cells) use ($selectedCols) {
            if ($selectedCols[0] === '*') {
                return $cells;
            }
            return array_map(function ($selectedCol) use ($cells) {
                if (!array_key_exists($selectedCol, $cells)) {
                    throw new SigmaSheetException('undefined column number ' . $selectedCol);
                }
                return trim($cells[$selectedCol]);
            }, $selectedCols);
        })->readAsChunks();
    }

    /**
     * @param callable|null $dataConsumer
     * @return array
     * @throws SigmaSheetException
     */
    public function readAsChunks(?callable $dataConsumer = null)
    {
        $skip = $this->skipInitialNumberOfRows;
        try {
            $reader = ReaderFactory::createFromFile($this->filePath);
            $reader->open($this->filePath);
            $dataChunks = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                if (!$this->shouldUseSheet($sheet)) {
                    continue;
                }
                foreach ($sheet->getRowIterator() as $row) {
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
                    if (!$this->readAsChunkCallbackSupported) {
                        continue;
                    }
                    if ($this->chunkSize !== 'all' && count($dataChunks) % $this->chunkSize == 0) {
                        $dataConsumer($dataChunks);
                        $dataChunks = [];
                    }
                }
            }
            if (!$this->readAsChunkCallbackSupported) {
                return $dataChunks;
            }
            if (count($dataChunks) != 0) {
                $dataConsumer($dataChunks);
            }

        } catch (SpoutException $spoutException) {
            throw new SigmaSheetException(($spoutException->getMessage()));
        } finally {
            $reader->close();
        }
    }

    private function shouldUseSheet($sheet)
    {
        if (!empty($this->sheetNames)) {
            return in_array($sheet->getName(), $this->sheetNames);
        }
        return in_array($sheet->getIndex(), $this->sheetIndexs);
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
        $this->sheetIndexs = is_array($sheetIndexs) ? $sheetIndexs : [$sheetIndexs];
        return $this;
    }

    public function addFilter(callable $filter): SheetReader
    {
        $this->actions[] = ['type' => static::ACTION_TYPE_FILTER, 'action' => $filter];
        return $this;
    }
}
