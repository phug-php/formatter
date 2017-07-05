<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
use Phug\Formatter\MarkupInterface;
use Phug\Parser\NodeInterface as ParserNode;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\NameTrait;
use Phug\Util\UnorderedArguments;
use SplObjectStorage;

class AssignmentElement extends AbstractElement
{
    use AttributeTrait;
    use NameTrait;

    public function __construct()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $name = $arguments->required('string');
        $attributes = $arguments->optional(SplObjectStorage::class);
        $markup = $arguments->optional(MarkupInterface::class);

        $this->setName($name);
        if ($attributes) {
            $this->getAttributes()->addAll($attributes);
        }
        if ($markup) {
            $this->setMarkup($markup);
        }

        $originNode = $arguments->optional(ParserNode::class);
        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($originNode, $parent, $children);
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
