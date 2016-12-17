<?php

namespace Phug\Formatter;

use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;

abstract class AbstractFormat implements FormatInterface, OptionInterface
{
    use OptionTrait;

    const PHP_CODE = '<?php %s ?>';

    protected $indentLevel = 0;

    public function __construct(array $options = null)
    {
        $this->setOptionsRecursive([
            'pretty'           => false,
            'element_handlers' => [
                AttributeElement::class => [$this, 'formatAttributeElement'],
                CodeElement::class      => [$this, 'formatCodeElement'],
                MarkupElement::class    => [$this, 'formatMarkupElement'],
            ],
        ], $options ?: []);
    }

    public function format($element)
    {
        if (is_string($element)) {
            return $element;
        }

        foreach ($this->getOption('element_handlers') as $className => $handler) {
            if (is_a($element, $className)) {
                return $handler($element);
            }
        }

        return '';
    }

    public function removeElementHandler($className)
    {
        $elementHandlers = $this->getOption('element_handlers');
        if (array_key_exists($className, $elementHandlers)) {
            unset($elementHandlers[$className]);
            $this->setOption('element_handlers', $elementHandlers);
        }

        return $this;
    }

    public function setElementHandler($className, callable $handler)
    {
        $elementHandlers = $this->getOption('element_handlers') ?: [];
        $elementHandlers[$className] = $handler;

        return $this->setOption('element_handlers', $elementHandlers);
    }

    protected function getNewLine()
    {
        $pretty = $this->getOption('pretty');

        return $pretty || $pretty === '' ? "\n" : '';
    }

    protected function getIndent()
    {
        $pretty = $this->getOption('pretty');
        if (!$pretty) {
            return '';
        }

        return str_repeat(is_string($pretty) ? $pretty : '  ', $this->indentLevel);
    }

    protected function formatCodeElement(CodeElement $element)
    {
        return sprintf(static::PHP_CODE, $this->format($element->getValue()));
    }
}
