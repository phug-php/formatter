<?php

namespace Phug\Formatter;

use Phug\Ast\Node;
use Phug\Ast\NodeInterface;
use Phug\Util\UnorderedArguments;

abstract class AbstractElement extends Node implements ElementInterface
{
    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);
    }

    public function dump()
    {
        $name = preg_replace('/^Phug\\\\.*\\\\([^\\\\]+)Element$/', '$1', get_class($this));
        if (method_exists($this, 'getName')) {
            $name .= ': '.$this->getName();
        }
        $lines = [$name];
        if ($this->hasChildren()) {
            foreach ($this->getChildren() as $child) {
                $dump = method_exists($child, 'dump')
                    ? $child->dump()
                    : get_class($child);
                foreach (explode("\n", $dump) as $line) {
                    $lines[] = '  '.$line;
                }
            }
        }

        return implode("\n", $lines);
    }
}
