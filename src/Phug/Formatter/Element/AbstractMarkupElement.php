<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\MarkupInterface;

abstract class AbstractMarkupElement extends AbstractAssignmentContainerElement implements MarkupInterface
{
    /**
     * Return true if the tag name is in the given list.
     *
     * @param array $tagList
     *
     * @return bool
     */
    public function belongsTo(array $tagList)
    {
        if (is_string($this->getName())) {
            return in_array(strtolower($this->getName()), $tagList);
        }

        return false;
    }
}
