<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractValueElement;
use Phug\Util\Partial\NameTrait;
use Phug\Util\UnorderedArguments;

class AttributeElement extends AbstractValueElement
{
    use NameTrait;

    public function __construct($name, $value)
    {
        $this->setName($name);
        $this->setValue($value);

        $arguments = new UnorderedArguments(array_slice(func_get_args(), 2));

        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);
    }
}
