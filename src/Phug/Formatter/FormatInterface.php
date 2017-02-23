<?php

namespace Phug\Formatter;

use Phug\Formatter;

/**
 * Mandatory methods for all output formats.
 */
interface FormatInterface
{
    public function __construct(Formatter $formatter = null);

    public function format($element);

    public function __invoke(ElementInterface $element);
}
