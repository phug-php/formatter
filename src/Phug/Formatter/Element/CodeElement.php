<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\ValueTrait;
use SplObjectStorage;

class CodeElement extends AbstractElement
{
    use ValueTrait;

    public function __construct($value)
    {
        $this->setValue($value);
    }
}
