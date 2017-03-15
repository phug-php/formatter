<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Util\UnorderedArguments;

class VariableElement extends AbstractElement
{
    /**
     * @var CodeElement
     */
    protected $variable;

    /**
     * @var ExpressionElement
     */
    protected $expression;

    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $variable = $arguments->optional(CodeElement::class);
        $expression = $arguments->optional(ExpressionElement::class);
        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        if ($variable !== null) {
            $this->setVariable($variable);
        }

        if ($expression !== null) {
            $this->setExpression($expression);
        }

        parent::__construct($parent, $children);
    }

    public function setVariable(CodeElement $variable)
    {
        $this->variable = $variable;
    }

    public function setExpression(ExpressionElement $expression)
    {
        $this->expression = $expression;
    }

    public function getVariable()
    {
        return $this->variable;
    }

    public function getExpression()
    {
        return $this->expression;
    }
}
