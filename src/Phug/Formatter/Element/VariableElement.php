<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
use Phug\Parser\NodeInterface as ParserNode;
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
        $originNode = $arguments->optional(ParserNode::class);
        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        if ($variable !== null) {
            $this->setVariable($variable);
        }

        if ($expression !== null) {
            $this->setExpression($expression);
        }

        parent::__construct($originNode, $parent, $children);
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
