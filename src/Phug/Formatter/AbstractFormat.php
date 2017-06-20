<?php

namespace Phug\Formatter;

use Phug\Formatter;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\CommentElement;
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

    const CLASS_ATTRIBUTE = '(is_array($_pug_temp = %s) ? implode(" ", $_pug_temp) : strval($_pug_temp))';
    const STRING_ATTRIBUTE = '
        (is_array($_pug_temp = %s) || (is_object($_pug_temp) && !method_exists($_pug_temp, "__toString"))
            ? json_encode($_pug_temp)
            : strval($_pug_temp))';
    const DYNAMIC_ATTRIBUTE = '
        (is_array($_pug_temp = is_array($_pug_temp = %s) && %s === "class"
            ? implode(" ", $_pug_temp)
            : $_pug_temp) || (is_object($_pug_temp) && !method_exists($_pug_temp, "__toString"))
                ? json_encode($_pug_temp)
                : strval($_pug_temp))';
    const EXPRESSION_IN_TEXT = '(is_bool($_pug_temp = %s) ? var_export($_pug_temp, true) : $_pug_temp)';
    const HTML_EXPRESSION_ESCAPE = 'htmlspecialchars(%s)';
    const HTML_TEXT_ESCAPE = 'htmlspecialchars';
    const PAIR_TAG = '%s%s%s';
    const TRANSFORM_EXPRESSION = '%s';
    const TRANSFORM_CODE = '%s';
    const TRANSFORM_RAW_CODE = '%s';
    const PHP_HANDLE_CODE = '<?php %s ?>';
    const PHP_BLOCK_CODE = ' {%s}';
    const PHP_NESTED_HTML = ' ?>%s<?php ';
    const PHP_DISPLAY_CODE = '<?= %s ?>';
    const DISPLAY_COMMENT = '<!-- %s -->';
    const DOCTYPE = '';
    const CUSTOM_DOCTYPE = '<!DOCTYPE %s>';
    const SAVE_VALUE = '%s=%s';

    /**
     * @var Formatter
     */
    protected $formatter;

    public function __construct(Formatter $formatter = null)
    {
        $patterns = [
            'class_attribute'        => static::CLASS_ATTRIBUTE,
            'string_attribute'       => static::STRING_ATTRIBUTE,
            'dynamic_attribute'      => static::DYNAMIC_ATTRIBUTE,
            'expression_in_text'     => static::EXPRESSION_IN_TEXT,
            'html_expression_escape' => static::HTML_EXPRESSION_ESCAPE,
            'html_text_escape'       => static::HTML_TEXT_ESCAPE,
            'pair_tag'               => static::PAIR_TAG,
            'transform_expression'   => static::TRANSFORM_EXPRESSION,
            'transform_code'         => static::TRANSFORM_CODE,
            'transform_raw_code'     => static::TRANSFORM_RAW_CODE,
            'php_handle_code'        => static::PHP_HANDLE_CODE,
            'php_display_code'       => static::PHP_DISPLAY_CODE,
            'php_block_code'         => static::PHP_BLOCK_CODE,
            'php_nested_html'        => static::PHP_NESTED_HTML,
            'display_comment'        => static::DISPLAY_COMMENT,
            'doctype'                => static::DOCTYPE,
            'custom_doctype'         => static::CUSTOM_DOCTYPE,
        ];
        foreach ($patterns as &$pattern) {
            if (is_string($pattern) && substr($pattern, 0, 1) === "\n") {
                $pattern = preg_replace('/\s+/', ' ', trim($pattern));
            }
        }
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
                'patterns'           => $patterns,
                'pretty'             => false,
                'element_handlers'   => [
                    AssignmentElement::class => [$this, 'formatAssignmentElement'],
                    AttributeElement::class  => [$this, 'formatAttributeElement'],
                    CodeElement::class       => [$this, 'formatCodeElement'],
                    CommentElement::class    => [$this, 'formatCommentElement'],
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

    /**
     * Handle PHP code with the pattern php_handle_code.
     *
     * @param string $phpCode
     *
     * @return string
     */
    public function handleCode($phpCode)
    {
        return $this->pattern('php_handle_code', $phpCode);
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

    /**
     * Format a code with transform_expression and tokens handlers.
     *
     * @param string $code
     * @param bool   $checked
     * @param bool   $noTransformation
     *
     * @return string
     */
    public function formatCode($code, $checked, $noTransformation = false)
    {
        if (!$noTransformation) {
            $code = $this->pattern(
                'transform_code',
                $this->pattern(
                    'transform_expression',
                    $this->pattern('transform_raw_code', $code)
                )
            );
        }

        return implode('', iterator_to_array($this->handleTokens(
            $code,
            $checked
        )));
    }

    protected function formatVariableElement(VariableElement $element)
    {
        $variable = $this->formatCode($element->getVariable()->getValue(), false);
        $expression = $element->getExpression();
        $value = $this->formatCode($expression->getValue(), $expression->isChecked());
        if ($expression->isEscaped()) {
            $value = $this->pattern('html_expression_escape', $value);
        }

        return $this->handleCode($this->pattern('save_value', $variable, $value));
    }

    protected function formatCodeElement(CodeElement $code)
    {
        $php = $this->formatCode($code->getValue(), false, !$code->isTransformationAllowed());

        if ($code->needAccolades()) {
            $php = preg_replace('/\s*\{\s*\}\s*$/', '', $php).$this->pattern(
                'php_block_code',
                $code->hasChildren()
                    ? $this->pattern('php_nested_html', $this->formatElementChildren($code, 0))
                    : ''
            );
        }

        return $this->handleCode($php);
    }

    protected function formatCommentElement(CommentElement $element)
    {
        return $this->getIndent().
            $this->pattern('display_comment', $element->getValue()).
            $this->getNewLine();
    }

    protected function formatAttributeValueAccordingToName($value, $name, $checked)
    {
        if ($name instanceof ExpressionElement) {
            return $this->exportHelper('stand_alone_attribute_assignment').
                '('.$this->formatCode($name->getValue(), $checked).', '.$value.')';
        }

        if ($name === 'class') {
            return $this->exportHelper('stand_alone_class_attribute_assignment').
                '('.$value.')';
        }

        if ($name === 'style') {
            return $this->exportHelper('stand_alone_style_attribute_assignment').
                '('.$value.')';
        }

        return $this->pattern('string_attribute', $value, $this->formatCode($name, $checked));
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

        $value = $this->formatCode($value, $code->isChecked(), !$code->isTransformationAllowed());

        if ($link = $code->getLink()) {
            if ($link instanceof AttributeElement) {
                $value = $this->formatAttributeValueAccordingToName($value, $link->getName(), $code->isChecked());
            }
        }

        if (preg_match('/\/\/[^\n]*$/', $value)) {
            $value .= "\n";
        }

        if (!$link) {
            $value = $this->pattern('expression_in_text', $value);
        }

        if ($code->isEscaped()) {
            $value = $this->pattern('html_expression_escape', $value);
        }

        return $this->pattern('php_display_code', $value);
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
