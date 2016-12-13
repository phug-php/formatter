<?php

namespace Phug\Formatter\Format;

use Phug\Formatter\AbstractFormat;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\MarkupElement;

class HtmlFormat extends AbstractFormat
{
    public function __construct(array $options = null)
    {

        $this->setOptionsRecursive([
            'element_handlers' => [
                MarkupElement::class => [$this, 'formatMarkupElement'],
            ],
        ]);
        $this->setOptionsRecursive($options ?: []);
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
