<?php

namespace Sigmasolutions\Sheets\Reader\Txt\Manager;

/**
 * Class Options
 * Readers' options holder
 */
abstract class Options
{
    // Common options
    public const SHOULD_PRESERVE_EMPTY_ROWS = 'shouldPreserveEmptyRows';
    public const ENCODING = 'encoding';


    // TXT specific options
    public const TXT_FIELD_SEPARATOR = 'txtFieldDelimiter';
}
