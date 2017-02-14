<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\NameTrait;

class AssignmentElement extends AbstractElement
{
    use AttributeTrait;
    use NameTrait;

    public function __construct($name, $attributes)
    {
        $this->setName($name);
        $this->getAttributes()->addAll($attributes);
    }
}
