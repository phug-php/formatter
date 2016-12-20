<?php

namespace Phug\Formatter;

use Phug\Formatter\Element\ExpressionElement;
use Phug\Util\Partial\ValueTrait;
use Phug\Util\UnorderedArguments;

abstract class AbstractValueElement extends AbstractElement
{
    use ValueTrait;

    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $value = $arguments->optional('string') ?: $arguments->optional(ExpressionElement::class);
        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);

        $this->setValue($value);
    }
}