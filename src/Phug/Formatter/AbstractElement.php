<?php

namespace Phug\Formatter;

use Phug\Ast\Node;
use Phug\Util\UnorderedArguments;

abstract class AbstractElement extends Node implements ElementInterface
{
    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $parent = $arguments->optional(NodeInterface ::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);
    }
}
