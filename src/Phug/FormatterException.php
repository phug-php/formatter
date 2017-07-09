<?php

namespace Phug;

use Phug\Util\Partial\PugFileLocationTrait;
use Phug\Util\PugFileLocationInterface;
use RuntimeException;

/**
 * Represents an exception that is thrown during tree-manipulation processes.
 */
class FormatterException extends RuntimeException implements PugFileLocationInterface
{
    use PugFileLocationTrait;
}
