<?php

namespace Phug\Formatter;

use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;

abstract class AbstractFormat implements FormatInterface, OptionInterface
{
    use OptionTrait;

    const HTML_EXPRESSION_ESCAPE = 'htmlspecialchars(%s)';
    const HTML_TEXT_ESCAPE = 'htmlspecialchars';
    const PHP_HANDLE_CODE = '<?php %s ?>';
    const PHP_BLOCK_CODE = ' {%s}';
    const PHP_NESTED_HTML = ' ?>%s<?php ';
    const PHP_DISPLAY_CODE = '<?= %s ?>';
    const DOCTYPE = '';
    const CUSTOM_DOCTYPE = '<!DOCTYPE %s>';

    protected $indentLevel = 0;

    public function __construct(array $options = null)
    {
        $this->setOptionsRecursive([
            'html_expression_escape' => static::HTML_EXPRESSION_ESCAPE,
            'html_text_escape'       => static::HTML_TEXT_ESCAPE,
            'php_handle_code'        => static::PHP_HANDLE_CODE,
            'php_display_code'       => static::PHP_DISPLAY_CODE,
            'php_block_code'         => static::PHP_BLOCK_CODE,
            'php_nested_html'        => static::PHP_NESTED_HTML,
            'doctype'                => static::DOCTYPE,
            'custom_doctype'         => static::CUSTOM_DOCTYPE,
            'pretty'                 => false,
            'element_handlers'       => [
                AttributeElement::class  => [$this, 'formatAttributeElement'],
                CodeElement::class       => [$this, 'formatCodeElement'],
                ExpressionElement::class => [$this, 'formatExpressionElement'],
                DoctypeElement::class    => [$this, 'formatDoctypeElement'],
                DocumentElement::class   => [$this, 'formatDocumentElement'],
                MarkupElement::class     => [$this, 'formatMarkupElement'],
                TextElement::class       => [$this, 'formatTextElement'],
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
        foreach ([
            // Exclude tokens before the variables
            -1 => [
                T_AS,
                T_EMPTY,
                T_GLOBAL,
                T_ISSET,
                T_OBJECT_OPERATOR,
                T_UNSET,
                T_UNSET_CAST,
                T_VAR,
                T_STATIC,
                T_PRIVATE,
                T_PROTECTED,
                T_PUBLIC,
            ],
            // Exclude tokens after the variables
            1 => [
                '[',
                T_AND_EQUAL,
                T_CONCAT_EQUAL,
                T_CURLY_OPEN,
                T_DIV_EQUAL,
                T_DOUBLE_ARROW,
                T_INC,
                T_MINUS_EQUAL,
                T_MOD_EQUAL,
                T_MUL_EQUAL,
                T_OBJECT_OPERATOR,
                T_OR_EQUAL,
                T_PLUS_EQUAL,
                defined('T_POW_EQUAL') ? T_POW_EQUAL : 'T_POW_EQUAL',
                T_SL_EQUAL,
                T_SR_EQUAL,
                T_XOR_EQUAL,
            ],
        ] as $direction => $exclusions) {
            $id = null;
            for ($i = 1; isset($tokens[$index + $direction * $i]); $i++) {
                $id = $tokens[$index + $direction * $i];
                if (is_array($id)) {
                    $id = $id[0];
                }
                // Ignore the following tokens
                if (in_array($id, [
                    T_COMMENT,
                    T_DOC_COMMENT,
                    T_WHITESPACE,
                ])) {
                    continue;
                }
                break;
            }

            if (in_array($id, $exclusions)) {
                return $variable;
            }
        }

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
        return implode('', iterator_to_array($this->handleTokens($code)));
    }

    protected function formatCodeElement(CodeElement $code)
    {
        $php = $this->formatCode($code->getValue());
        if ($code->hasChildren()) {
            $php .= $this->pattern('php_block_code', $this->pattern('php_nested_html', $this->formatElementChildren($code)));
        }

        return $this->pattern('php_handle_code', $php);
    }

    protected function formatExpressionElement(ExpressionElement $code)
    {
        $value = $code->getValue();
        if ($code->isEscaped()) {
            $value = $this->pattern('html_expression_escape', $value);
        }

        return $this->pattern('php_display_code', $this->formatCode($value));
    }

    protected function formatTextElement(TextElement $text)
    {
        $value = $text->getValue();
        if ($text->isEscaped()) {
            $value = $this->pattern('html_text_escape', $value);
        }
        if ($text->getPreviousSibling() instanceof TextElement) {
            $value = ' '.$value;
        }

        return $this->format($value);
    }

    protected function formatDoctypeElement(DoctypeElement $doctype)
    {
        $type = $doctype->getValue();
        $pattern = $type ? 'custom_doctype' : 'doctype';

        return $this->pattern($pattern, $type);
    }

    protected function formatElementChildren(ElementInterface $element, $indentStep = 1)
    {
        $this->indentLevel += $indentStep;
        $content = implode('', array_map([$this, 'format'], $element->getChildren()));
        $this->indentLevel -= $indentStep;

        return $content;
    }

    protected function formatDocumentElement(DocumentElement $document)
    {
        return $this->formatElementChildren($document, 0);
    }
}
