<?php

namespace Sigmasolutions\Sheets\Reader\Creator;

/**
 * Class Type
 * This class references the supported types
 */
abstract class ReaderType
{
    const CSV = 'csv';
    const XLSX = 'xlsx';
    const ODS = 'ods';
    const TXT = 'txt';
    const LOG = 'log';
}
