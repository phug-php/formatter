<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement

abstract class MarkupElement implements AbstractElement
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $tagName;
}
