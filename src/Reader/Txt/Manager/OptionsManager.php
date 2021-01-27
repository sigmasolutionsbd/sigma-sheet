<?php

namespace Sigmasolutions\Sheets\Reader\Txt\Manager;

use Box\Spout\Common\Helper\EncodingHelper;
use Box\Spout\Common\Manager\OptionsManagerAbstract;

/**
 * Class OptionsManager
 * TXT Reader options manager
 */
class OptionsManager extends OptionsManagerAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedOptions()
    {
        return [
            Options::TXT_FIELD_SEPARATOR,
            Options::SHOULD_PRESERVE_EMPTY_ROWS,
            Options::ENCODING
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultOptions()
    {
        $this->setOption(Options::TXT_FIELD_SEPARATOR, null);
        $this->setOption(Options::SHOULD_PRESERVE_EMPTY_ROWS, false);
        $this->setOption(Options::ENCODING, EncodingHelper::ENCODING_UTF8);
    }
}
