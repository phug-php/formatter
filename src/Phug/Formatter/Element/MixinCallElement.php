<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
use Phug\Formatter\MarkupInterface;
use Phug\Formatter\Partial\MarkupTrait;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\NameTrait;
use Phug\Util\UnorderedArguments;
use SplObjectStorage;

class MixinCallElement extends AbstractElement implements MarkupInterface
{
    use AttributeTrait;
    use NameTrait;
    use MarkupTrait;

    /**
     * @var array<ExpressionElement>
     */
    protected $arguments = [];

    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $name = $arguments->optional('string');
        $parent = $arguments->optional(NodeInterface ::class);
        $attributes = $arguments->optional(SplObjectStorage::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);

        $this->setName($name);
        if ($attributes) {
            $this->getAttributes()->addAll($attributes);
        }
    }

    /**
     * Pass an argument value in a mixin call.
     *
     * @param ExpressionElement $value argument value
     *
     * @return $this
     */
    public function addArgument(ExpressionElement $expression)
    {
        $packed = substr($expression->getValue(), 0, 3) === '...';
        if ($packed) {
            $expression->setValue(substr($expression->getValue(), 3));
        }

        $this->arguments[] = [$packed, $expression];

        return $this;
    }

    /**
     * Get argument values.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
