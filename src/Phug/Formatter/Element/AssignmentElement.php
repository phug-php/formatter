<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
use Phug\Formatter\MarkupInterface;
use Phug\Parser\NodeInterface as ParserNode;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\NameTrait;

class AssignmentElement extends AbstractElement
{
    use AttributeTrait;
    use NameTrait;

    /**
     * AssignmentElement constructor.
     *
     * @param string                 $name
     * @param \SplObjectStorage|null $attributes
     * @param MarkupInterface|null   $markup
     * @param ParserNode|null        $originNode
     * @param NodeInterface|null     $parent
     * @param array|null             $children
     */
    public function __construct(
        $name,
        \SplObjectStorage $attributes = null,
        MarkupInterface $markup = null,
        ParserNode $originNode = null,
        NodeInterface $parent = null,
        array $children = null
    ) {
        parent::__construct($originNode, $parent, $children);

        $this->setName($name);

        if ($attributes) {
            $this->getAttributes()->addAll($attributes);
        }

        if ($markup) {
            $this->setMarkup($markup);
        }
    }

    /**
     * @var MarkupElement
     */
    private $markup;

    /**
     * Set markup subject.
     *
     * @param MarkupInterface $markup
     */
    public function setMarkup(MarkupInterface $markup)
    {
        $this->markup = $markup;
    }

    /**
     * @return MarkupElement
     */
    public function getMarkup()
    {
        return $this->markup;
    }

    /**
     * Detach the assignment from its markup.
     */
    public function detach()
    {
        return $this->markup->removedAssignment($this);
    }
}
