<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Util\Partial\NameTrait;
use Phug\Util\Partial\ValueTrait;

class AttributeElement extends AbstractElement
{
    use NameTrait;
    use ValueTrait;

    public function __construct($name, $value)
    {
        $this->setName($name);
        $this->setValue($value);
    }
}
