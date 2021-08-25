<?php

namespace Sigmasolutions\Sheets\Reader\Creator;

/**
 * Class Type
 * This class references the supported types
 */
abstract class ReaderType
{
    public const CSV = 'csv';
    public const XLSX = 'xlsx';
    public const ODS = 'ods';
    public const TXT = 'txt';
    public const LOG = 'log';
}
