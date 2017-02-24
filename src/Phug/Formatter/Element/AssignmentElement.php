<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
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
        $markup = $arguments->required(MarkupElement::class);

        $this->setName($name);
        if ($attributes) {
            $this->getAttributes()->addAll($attributes);
        }
        $this->setMarkup($markup);

        $parent = $arguments->optional(NodeInterface::class);
        $children = $arguments->optional('array');

        $arguments->noMoreDefinedArguments();

        parent::__construct($parent, $children);
    }

    /**
     * @var MarkupElement
     */
    private $markup;

    /**
     * Set markup subject.
     *
     * @param MarkupElement $markup
     */
    public function setMarkup(MarkupElement $markup)
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
