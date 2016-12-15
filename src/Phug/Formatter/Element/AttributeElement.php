<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Util\Partial\PairTrait;

class AttributeElement extends AbstractElement
{
    use PairTrait;

    public function __construct($name, $value)
    {
        $this->setKey($name);
        $this->setItem($value);
    }
}
