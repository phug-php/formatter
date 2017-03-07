<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;

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

    public function __construct(CodeElement $variable = null, ExpressionElement $expression = null)
    {
        $variable && $this->setVariable($variable);
        $expression && $this->setExpression($expression);
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
