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
            'php_token_handlers' => [
                T_VARIABLE => [$this, 'handleVariable'],
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
        return $this->unsetOption(['element_handlers', $className]);
    }

    public function setElementHandler($className, callable $handler)
    {
        return $this->setOption(['element_handlers', $className], $handler);
    }

    public function removePhpTokenHandler($phpTokenId)
    {
        return $this->unsetOption(['php_token_handlers', $phpTokenId]);
    }

    public function setPhpTokenHandler($phpTokenId, $handler)
    {
        return $this->setOption(['php_token_handlers', $phpTokenId], $handler);
    }

    protected function handleVariable($variable, $index, &$tokens)
    {
        return '(isset('.$variable.') ? '.$variable." : '')";
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

    protected function handleTokens($code)
    {
        $phpTokenHandler = $this->getOption('php_token_handlers');
        $tokens = array_slice(token_get_all('<?php '.$code), 1);

        foreach ($tokens as $index => $token) {
            $id = $token;
            $text = $token;
            if (!is_string($id)) {
                list($id, $text) = $token;
            }
            if (!isset($phpTokenHandler[$id])) {
                yield $text;

                continue;
            }
            if (is_string($phpTokenHandler[$id])) {
                yield sprintf($phpTokenHandler[$id], $text);

                continue;
            }

            yield $phpTokenHandler[$id]($text, $index, $tokens);
        }
    }

    protected function formatCode($code)
    {
        return implode('', array_map($this->handleTokens($code)));
    }

    protected function formatCodeElement(CodeElement $code)
    {
        return $this->pattern('php_handle_code', $this->formatCode($code->getValue()));
    }

    protected function formatExpressionElement(ExpressionElement $code)
    {
        $value = $code->getValue();
        if ($code->isEscaped()) {
            $value = $this->pattern('html_escape', $value);
        }

        return $this->pattern('php_display_code', $this->formatCode($value));
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
