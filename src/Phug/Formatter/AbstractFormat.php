<?php

namespace Phug\Formatter;

use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;

abstract class AbstractFormat implements FormatInterface, OptionInterface
{
    use OptionTrait;

    const HTML_ESCAPE = 'htmlspecialchars(%s)';
    const PHP_HANDLE_CODE = '<?php %s ?>';
    const PHP_DISPLAY_CODE = '<?= %s ?>';
    const DOCTYPE = '';
    const CUSTOM_DOCTYPE = '<!DOCTYPE %s>';

    protected $indentLevel = 0;

    public function __construct(array $options = null)
    {
        $this->setOptionsRecursive([
            'html_escape'      => static::HTML_ESCAPE,
            'php_handle_code'  => static::PHP_HANDLE_CODE,
            'php_display_code' => static::PHP_DISPLAY_CODE,
            'doctype'          => static::DOCTYPE,
            'custom_doctype'   => static::CUSTOM_DOCTYPE,
            'pretty'           => false,
            'element_handlers' => [
                AttributeElement::class  => [$this, 'formatAttributeElement'],
                CodeElement::class       => [$this, 'formatCodeElement'],
                ExpressionElement::class => [$this, 'formatExpressionElement'],
                DoctypeElement::class    => [$this, 'formatDoctypeElement'],
                DocumentElement::class   => [$this, 'formatDocumentElement'],
                MarkupElement::class     => [$this, 'formatMarkupElement'],
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

    protected function pattern($patternOption)
    {
        $pattern = $this->getOption($patternOption);
        $args = func_get_args();
        $args[0] = $pattern;
        $function = 'sprintf';
        if (is_callable($pattern)) {
            $function = $pattern;
            $args = array_slice($args, 1);
        }

        return call_user_func_array($function, $args);
    }

    protected function formatCodeElement(CodeElement $code)
    {
        return $this->pattern('php_handle_code', $this->format($code->getValue()));
    }

    protected function formatExpressionElement(ExpressionElement $code)
    {
        $value = $code->getValue();
        if ($code->isEscaped()) {
            $value = $this->pattern('html_escape', $value);
        }

        return $this->pattern('php_display_code', $this->format($value));
    }

    protected function formatDoctypeElement(DoctypeElement $doctype)
    {
        $type = $doctype->getValue();
        $pattern = $type ? 'custom_doctype' : 'doctype';

        return $this->pattern($pattern, $type);
    }

    protected function formatTagChildren(ElementInterface $element, $indentStep = 1)
    {
        $this->indentLevel += $indentStep;
        $content = implode('', array_map([$this, 'format'], $element->getChildren()));
        $this->indentLevel -= $indentStep;

        return $content;
    }

    protected function formatDocumentElement(DocumentElement $document)
    {
        return $this->formatTagChildren($document, 0);
    }
}
