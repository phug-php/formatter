<?php

namespace Phug\Formatter;

use Phug\Formatter;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Formatter\Element\VariableElement;
use Phug\Formatter\Partial\HandleVariable;
use Phug\Formatter\Partial\PatternTrait;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;

abstract class AbstractFormat implements FormatInterface, OptionInterface
{
    use HandleVariable;
    use OptionTrait;
    use PatternTrait;

    const HTML_EXPRESSION_ESCAPE = 'htmlspecialchars(%s)';
    const HTML_TEXT_ESCAPE = 'htmlspecialchars';
    const PHP_HANDLE_CODE = '<?php %s ?>';
    const PHP_BLOCK_CODE = ' {%s}';
    const PHP_NESTED_HTML = ' ?>%s<?php ';
    const PHP_DISPLAY_CODE = '<?= %s ?>';
    const DOCTYPE = '';
    const CUSTOM_DOCTYPE = '<!DOCTYPE %s>';
    const SAVE_VALUE = '%s=%s';

    /**
     * @var Formatter
     */
    protected $formatter;

    public function __construct(Formatter $formatter = null)
    {
        $this
            ->setFormatter($formatter ?: new Formatter())
            ->setOptionsRecursive([
                'pattern'             => function ($pattern) {
                    $args = func_get_args();
                    $args[0] = $pattern;
                    $function = 'sprintf';
                    if (is_callable($pattern)) {
                        $function = $pattern;
                        $args = array_slice($args, 1);
                    }

                    return call_user_func_array($function, $args);
                },
                'patterns'           => [
                    'html_expression_escape' => static::HTML_EXPRESSION_ESCAPE,
                    'html_text_escape'       => static::HTML_TEXT_ESCAPE,
                    'php_handle_code'        => static::PHP_HANDLE_CODE,
                    'php_display_code'       => static::PHP_DISPLAY_CODE,
                    'php_block_code'         => static::PHP_BLOCK_CODE,
                    'php_nested_html'        => static::PHP_NESTED_HTML,
                    'doctype'                => static::DOCTYPE,
                    'custom_doctype'         => static::CUSTOM_DOCTYPE,
                ],
                'pretty'             => false,
                'element_handlers'   => [
                    AssignmentElement::class => [$this, 'formatAssignmentElement'],
                    AttributeElement::class  => [$this, 'formatAttributeElement'],
                    CodeElement::class       => [$this, 'formatCodeElement'],
                    ExpressionElement::class => [$this, 'formatExpressionElement'],
                    DoctypeElement::class    => [$this, 'formatDoctypeElement'],
                    DocumentElement::class   => [$this, 'formatDocumentElement'],
                    MarkupElement::class     => [$this, 'formatMarkupElement'],
                    TextElement::class       => [$this, 'formatTextElement'],
                    VariableElement::class   => [$this, 'formatVariableElement'],
                ],
                'php_token_handlers' => [
                    T_VARIABLE => [$this, 'handleVariable'],
                ],
            ], $this->formatter->getOptions() ?: [])
            ->registerHelper('pattern', $this->getOption('pattern'))
            ->addPatterns($this->getOption('patterns'));
    }

    /**
     * @param Formatter $formatter
     *
     * @return $this
     */
    public function setFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
        $format = $this;

        return $this->registerHelper(
            'dependencies_storage',
            $formatter->getOption('dependencies_storage')
        )->registerHelper(
            'helper_prefix',
            static::class.'::'
        )->provideHelper(
            'get_helper',
            [
                'dependencies_storage',
                'helper_prefix',
                function ($dependenciesStorage, $prefix) use ($format) {
                    return function ($name) use ($dependenciesStorage, $prefix, $format) {
                        if (!isset($$dependenciesStorage)) {
                            return $format->getHelper($name);
                        }

                        $storage = $$dependenciesStorage;

                        return $storage[$prefix.$name];
                    };
                },
            ]
        );
    }

    /**
     * @param $element
     *
     * @return string
     */
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

    /**
     * @param $className
     *
     * @return $this
     */
    public function removeElementHandler($className)
    {
        return $this->unsetOption(['element_handlers', $className]);
    }

    /**
     * @param          $className
     * @param callable $handler
     *
     * @return $this
     */
    public function setElementHandler($className, callable $handler)
    {
        return $this->setOption(['element_handlers', $className], $handler);
    }

    /**
     * @param $phpTokenId
     *
     * @return $this
     */
    public function removePhpTokenHandler($phpTokenId)
    {
        return $this->unsetOption(['php_token_handlers', $phpTokenId]);
    }

    /**
     * @param $phpTokenId
     * @param $handler
     *
     * @return $this
     */
    public function setPhpTokenHandler($phpTokenId, $handler)
    {
        return $this->setOption(['php_token_handlers', $phpTokenId], $handler);
    }

    protected function getNewLine()
    {
        $pretty = $this->getOption('pretty');

        return $pretty || $pretty === '' ? PHP_EOL : '';
    }

    protected function getIndent()
    {
        $pretty = $this->getOption('pretty');
        if (!$pretty) {
            return '';
        }

        return str_repeat(is_string($pretty) ? $pretty : '  ', $this->formatter->getLevel());
    }

    protected function pattern($patternOption)
    {
        $args = func_get_args();
        $args[0] = $this->patternName($patternOption);

        return call_user_func_array([$this, 'callHelper'], $args);
    }

    protected function handleTokens($code, $checked)
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

            yield $phpTokenHandler[$id]($text, $index, $tokens, $checked);
        }
    }

    protected function formatCode($code, $checked)
    {
        return implode('', iterator_to_array($this->handleTokens($code, $checked)));
    }

    protected function formatVariableElement(VariableElement $element)
    {
        $variable = $this->formatCode($element->getVariable()->getValue(), false);
        $expression = $element->getExpression();
        $value = $this->formatCode($expression->getValue(), $expression->isChecked());
        if ($expression->isEscaped()) {
            $value = $this->pattern('html_expression_escape', $value);
        }

        return $this->pattern('php_handle_code', $this->pattern('save_value', $variable, $value));
    }

    protected function formatCodeElement(CodeElement $code)
    {
        $php = $this->formatCode($code->getValue(), false);
        if ($code->hasChildren()) {
            $php .= $this->pattern(
                'php_block_code',
                $this->pattern('php_nested_html', $this->formatElementChildren($code))
            );
        }

        return $this->pattern('php_handle_code', $php);
    }

    protected function formatExpressionElement(ExpressionElement $code)
    {
        $value = $code->getValue();

        if ($code->hasStaticValue()) {
            $value = strval(eval('return '.$value.';'));
            if ($code->isEscaped()) {
                $value = $this->pattern('html_text_escape', $value);
            }

            return $value;
        }

        if ($code->isEscaped()) {
            $value = $this->pattern('html_expression_escape', $value);
        }

        return $this->pattern('php_display_code', $this->formatCode($value, $code->isChecked()));
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

        return $this->pattern($pattern, $type).$this->getNewLine();
    }

    protected function formatElementChildren(ElementInterface $element, $indentStep = 1)
    {
        $indentLevel = $this->formatter->getLevel();
        $this->formatter->setLevel($indentLevel + $indentStep);
        $content = '';
        $previous = null;
        foreach ($element->getChildren() as $child) {
            $childContent = $this->formatter->format($child);
            if ($child instanceof CodeElement &&
                $previous instanceof CodeElement &&
                $previous->isCodeBlock()
            ) {
                $content = substr($content, 0, -2);
                $childContent = preg_replace('/^<\?(?:php)?\s/', '', $childContent);
            }
            $content .= $childContent;
            $previous = $child;
        }
        $this->formatter->setLevel($indentLevel);

        return $content;
    }

    protected function formatDocumentElement(DocumentElement $document)
    {
        return $this->formatElementChildren($document, 0);
    }
}
