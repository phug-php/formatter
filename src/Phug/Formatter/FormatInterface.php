<?php

namespace Phug\Formatter;

use Phug\Formatter\ElementInterface;

/**
 * Mandatory methods for all output formats.
 */
interface FormatInterface
{
    public function __invoke(ElementInterface $element);
}
