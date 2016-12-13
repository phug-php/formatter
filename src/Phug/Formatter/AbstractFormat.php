<?php

namespace Phug\Formatter;

use Phug\Formatter\Element\MarkupElement;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;

abstract class AbstractFormat implements FormatInterface, OptionInterface
{
    use OptionTrait;

    public function __construct(array $options = null)
    {

        $this->options = array_replace_recursive([
            'element_handlers' => [
                MarkupElement::class => [$this, 'formatMarkupElement'],
            ],
        ], $options ?: []);
    }

    public function removeElementHandler($className)
    {

        if (array_key_exists($className, $this->options['element_handlers'])) {
            unset($this->options['element_handlers'][$className]);
        }
    }

    public function setElementHandler($className, callable $handler)
    {

        $this->options['element_handlers'][$className] = $handler;
    }
}
