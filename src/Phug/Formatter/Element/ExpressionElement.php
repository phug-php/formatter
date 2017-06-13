<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractValueElement;
use Phug\Util\Partial\CheckTrait;
use Phug\Util\Partial\EscapeTrait;

class ExpressionElement extends AbstractValueElement
{
    use CheckTrait;
    use EscapeTrait;

    /**
     * An element or any context representation the expression is linked to.
     *
     * @var mixed
     */
    protected $link;

    /**
     * @var bool
     */
    protected $transformable = true;

    /**
     * Link the expression to a meaningful context such as an attribute element.
     *
     * @param mixed $link
     *
     * @var $this
     */
    public function linkTo($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Prevent the expression from being transformed by customization patterns.
     */
    public function preventFromTransformation()
    {
        $this->transformable = false;
    }

    /**
     * @return bool
     */
    public function isTransformationAllowed()
    {
        return $this->transformable;
    }
}
