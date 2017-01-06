<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Formatter\Partial\ArgumentsTrait;
use Phug\Util\Partial\NameTrait;
use Phug\Util\UnorderedArguments;

class MixinDeclarationElement extends AbstractElement
{
    use ArgumentsTrait;
    use NameTrait;

    public function __construct($name)
    {
        $arguments = new UnorderedArguments(func_get_args());

        $name = $arguments->optional('string') ?: $arguments->optional(ExpressionElement::class);
        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $this->setName($name);

        parent::__construct($parent, $children);
    }
}
