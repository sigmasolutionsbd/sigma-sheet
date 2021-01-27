<?php

namespace Sigmasolutions\Sheets\Reader\Creator;

use Box\Spout\Common\Creator\HelperFactory;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\CSV\Creator\InternalEntityFactory as CSVInternalEntityFactory;
use Box\Spout\Reader\CSV\Manager\OptionsManager as CSVOptionsManager;
use Box\Spout\Reader\CSV\Reader as CSVReader;
use Box\Spout\Reader\ODS\Creator\HelperFactory as ODSHelperFactory;
use Box\Spout\Reader\ODS\Creator\InternalEntityFactory as ODSInternalEntityFactory;
use Box\Spout\Reader\ODS\Creator\ManagerFactory as ODSManagerFactory;
use Box\Spout\Reader\ODS\Manager\OptionsManager as ODSOptionsManager;
use Box\Spout\Reader\ODS\Reader as ODSReader;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\XLSX\Creator\HelperFactory as XLSXHelperFactory;
use Box\Spout\Reader\XLSX\Creator\InternalEntityFactory as XLSXInternalEntityFactory;
use Box\Spout\Reader\XLSX\Creator\ManagerFactory as XLSXManagerFactory;
use Box\Spout\Reader\XLSX\Manager\OptionsManager as XLSXOptionsManager;
use Box\Spout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\XLSX\Reader as XLSXReader;
use Sigmasolutions\Sheets\Reader\Txt\Creator\InternalEntityFactory as TxtInternalEntityFactory;
use Sigmasolutions\Sheets\Reader\Txt\Manager\Options;
use Sigmasolutions\Sheets\Reader\Txt\Manager\OptionsManager as TxtOptionsManager;
use Sigmasolutions\Sheets\Reader\Txt\Reader as TxtReader;

/**
 * Class ReaderFactory
 * This factory is used to create readers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 */
class ReaderFactory
{
    /**
     * Creates a reader by file extension
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     * @return ReaderInterface
     * @throws UnsupportedTypeException
     */
    public static function createFromFile(string $path)
    {
        $extension = \strtolower(\pathinfo($path, PATHINFO_EXTENSION));

        return self::createFromType($extension);
    }

    /**
     * This creates an instance of the appropriate reader, given the type of the file to be read
     *
     * @param string $readerType Type of the reader to instantiate
     * @return ReaderInterface
     * @throws UnsupportedTypeException
     */
    public static function createFromType(string $readerType)
    {
        switch ($readerType) {
            case ReaderType::CSV:
                return self::createCSVReader();
            case ReaderType::XLSX:
                return self::createXLSXReader();
            case ReaderType::ODS:
                return self::createODSReader();
            case ReaderType::TXT:
            case ReaderType::LOG:
                return self::createTxtReader();
            default:
                throw new UnsupportedTypeException('No readers supporting the given type: ' . $readerType);
        }
    }

    /**
     * @return CSVReader
     */
    private static function createCSVReader(): CSVReader
    {
        $optionsManager = new CSVOptionsManager();
        $helperFactory = new HelperFactory();
        $entityFactory = new CSVInternalEntityFactory($helperFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new CSVReader($optionsManager, $globalFunctionsHelper, $entityFactory);
    }

    /**
     * @return XLSXReader
     */
    private static function createXLSXReader(): XLSXReader
    {
        $optionsManager = new XLSXOptionsManager();
        $helperFactory = new XLSXHelperFactory();
        $managerFactory = new XLSXManagerFactory($helperFactory, new CachingStrategyFactory());
        $entityFactory = new XLSXInternalEntityFactory($managerFactory, $helperFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new XLSXReader($optionsManager, $globalFunctionsHelper, $entityFactory, $managerFactory);
    }

    /**
     * @return ODSReader
     */
    private static function createODSReader(): ODSReader
    {
        $optionsManager = new ODSOptionsManager();
        $helperFactory = new ODSHelperFactory();
        $managerFactory = new ODSManagerFactory();
        $entityFactory = new ODSInternalEntityFactory($helperFactory, $managerFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new ODSReader($optionsManager, $globalFunctionsHelper, $entityFactory);
    }

    /**
     * @return TxtReader
     */
    public static function createTxtReader(): TxtReader
    {
        $optionsManager = new TxtOptionsManager();
        $optionsManager->setOption(Options::TXT_FIELD_SEPARATOR, ' ');

        $helperFactory = new HelperFactory();
        $entityFactory = new TxtInternalEntityFactory($helperFactory);
        $globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();

        return new TxtReader($optionsManager, $globalFunctionsHelper, $entityFactory);
    }
}
