<?php

namespace Sigmasolutions\Sheets\Reader\Txt\Manager;

/**
 * Class Options
 * Readers' options holder
 */
abstract class Options
{
    // Common options
    const SHOULD_PRESERVE_EMPTY_ROWS = 'shouldPreserveEmptyRows';
    const ENCODING = 'encoding';


    // TXT specific options
    const TXT_FIELD_SEPARATOR = 'txtFieldDelimiter';
}
