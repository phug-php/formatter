<?php

namespace Phug\Formatter;

use Phug\Util\Partial\ValueTrait;
use Phug\Util\UnOrderedArguments;

abstract class AbstractValueElement extends AbstractElement
{
    use ValueTrait;

    public function __construct()
    {
        $arguments = new UnOrderedArguments(func_get_args());

        $value = $arguments->optional('string');
        $parent = $arguments->optional(NodeInterface ::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);

        $this->setName($value);
    }
}
