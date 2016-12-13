<?php

namespace Phug\Formatter\Format;

use Phug\Formatter\AbstractFormat;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\MarkupElement;
use Phug\Util\Partial\OptionTrait;

class HtmlFormat extends AbstractFormat
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

    public function __invoke(ElementInterface $element)
    {

        return $this->format($element);
    }

    public function format(ElementInterface $element)
    {

        foreach ($this->getOption('element_handlers') as $className => $handler) {
            if (is_a($element, $className)) {
                return $handler($element);
            }
        }

        return '';
    }

    public function formatMarkupElement(MarkupElement $element)
    {
        return '<' . $element->getTagName() . '>';
    }
}
