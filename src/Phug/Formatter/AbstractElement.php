<?php

namespace Phug\Formatter;

use Phug\Ast\Node;
use Phug\Util\UnOrderedArguments;

abstract class AbstractElement extends Node implements ElementInterface
{
    public function __construct()
    {
        $arguments = new UnOrderedArguments(func_get_args());

        $parent = $arguments->optional(NodeInterface ::class);
        $attributes = $arguments->optional(SplObjectStorage::class);

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);
    }
}
