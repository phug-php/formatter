<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;

class MarkupElement extends AbstractElement
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $tagName;

    public function __construct($tagName, array $attributes = null)
    {
        $this->tagName = $tagName;
        $this->attributes = $attributes ?: [];
    }

    public function getTagName()
    {
        return $this->tagName;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}
