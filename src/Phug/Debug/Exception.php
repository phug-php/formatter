<?php

namespace Phug\Debug;

use Phug\Util\Partial\PugFileLocationTrait;
use Phug\Util\PugFileLocationInterface;

class Exception extends \Exception implements PugFileLocationInterface
{
    use PugFileLocationTrait;

    public function __construct($message, $code, $previous, $file, $line, $offset)
    {
        $this->setPugFile($file);
        $this->setPugLine($line);
        $this->setPugOffset($offset);
        parent::__construct($message, $code, $previous);
    }
}
