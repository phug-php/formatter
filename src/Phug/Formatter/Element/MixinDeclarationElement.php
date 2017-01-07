<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Util\Partial\NameTrait;
use Phug\Util\UnorderedArguments;

class MixinDeclarationElement extends AbstractElement
{
    use NameTrait;

    /**
     * @var array<string>
     */
    protected $arguments = [];

    /**
     * @var string
     */
    protected $variadic = null;

    public function __construct($name)
    {
        $arguments = new UnorderedArguments(func_get_args());

        $name = $arguments->optional('string') ?: $arguments->optional(ExpressionElement::class);
        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $this->setName($name);

        parent::__construct($parent, $children);
    }

    /**
     * Register an argument name in a mixin declaration.
     *
     * @param string $name argument name
     *
     * @return $this
     */
    public function addArgument($name)
    {
        if (substr($name, 0, 3) === '...') {
            return $this->setVariadic(substr($name, 3));
        }

        $this->arguments[] = $name;

        return $this;
    }

    /**
     * Register an variadic argument name (rest arguments) in a mixin declaration.
     *
     * @param string $name argument name
     *
     * @return $this
     */
    public function setVariadic($name)
    {
        $this->variadic = $name;

        return $this;
    }

    /**
     * Get argument names.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get variadic argument name or null if not set.
     *
     * @return string|null
     */
    public function getVariadic()
    {
        return $this->variadic;
    }
}
