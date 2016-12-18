<?php

namespace Phug\Formatter;

/**
 * Mandatory methods for all output formats.
 */
interface FormatInterface
{
    public function __invoke(ElementInterface $element);
}
