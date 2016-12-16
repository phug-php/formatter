<?php

namespace Phug\Formatter\Element;

use Phug\Ast\NodeInterface;
use Phug\Formatter\AbstractElement;
use Phug\Util\Partial\AttributeTrait;
use Phug\Util\Partial\NameTrait;
use SplObjectStorage;

class MarkupElement extends AbstractElement
{
    use AttributeTrait;
    use NameTrait;

    public function __construct(
        $name,
        NodeInterface $parent = null,
        SplObjectStorage $attributes = null,
        array $children = null
    ) {
        parent::__construct($parent, $children);

        $this->setName($name);
        if ($attributes) {
            $this->getAttributes()->addAll($attributes);
        }
    }

    public function getAttribute($name)
    {
        foreach ($this->getAttributes() as $attribute) {
            if ($attribute->getKey() === $name) {
                return $attribute->getItem();
            }
        }
    }

    public function belongsTo(array $tagList)
    {
        if (is_string($this->getName())) {
            return in_array(strtolower($this->getName()), $tagList);
        }

        return false;
    }
}
