<?php

namespace Phug\Formatter\Format;

use Phug\Formatter\AbstractFormat;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\MarkupInterface;

class XmlFormat extends AbstractFormat
{
    const DOCTYPE = '<?xml version="1.0" encoding="utf-8" ?>';
    const OPEN_PAIR_TAG = '<%s>';
    const CLOSE_PAIR_TAG = '</%s>';
    const SELF_CLOSING_TAG = '<%s />';
    const ATTRIBUTE_PATTERN = ' %s="%s"';
    const BOOLEAN_ATTRIBUTE_PATTERN = ' %s="%s"';
    const BUFFER_VARIABLE = '$__value';
    const SAVE_VALUE = '%s=%s';
    const TEST_VALUE = 'isset(%s)';

    public function __construct(array $options = null)
    {
        $this->setOptions([
            'open_pair_tag'             => static::OPEN_PAIR_TAG,
            'close_pair_tag'            => static::CLOSE_PAIR_TAG,
            'self_closing_tag'          => static::SELF_CLOSING_TAG,
            'attribute_pattern'         => static::ATTRIBUTE_PATTERN,
            'boolean_attribute_pattern' => static::BOOLEAN_ATTRIBUTE_PATTERN,
            'save_value'                => static::SAVE_VALUE,
            'test_value'                => static::TEST_VALUE,
            'buffer_variable'           => static::BUFFER_VARIABLE,
        ]);
        parent::__construct($options);
    }

    public function __invoke(ElementInterface $element)
    {
        return $this->format($element);
    }

    protected function isSelfClosingTag(MarkupInterface $element)
    {
        return !$element->hasChildren();
    }

    protected function isBlockTag(MarkupInterface $element)
    {
        return true;
    }

    protected function formatAttributeElement(AttributeElement $element)
    {
        $value = $element->getItem();
        $key = $element->getKey();
        if ($value instanceof ExpressionElement) {
            if (strtolower($value->getValue()) === 'true') {
                $formattedValue = null;
                if ($key instanceof ExpressionElement) {
                    $bufferVariable = $this->pattern('buffer_variable');
                    $key = $this->pattern(
                        'php_display_code',
                        $this->pattern(
                            'save_value',
                            $bufferVariable,
                            $this->formatCode($key->getValue())
                        )
                    );
                    $value = new ExpressionElement($bufferVariable);
                    $formattedValue = $this->format($value);
                }
                $formattedKey = $this->format($key);
                $formattedValue = $formattedValue ?: $formattedKey;

                return $this->pattern(
                    'boolean_attribute_pattern',
                    $formattedKey,
                    $formattedValue
                );
            }
            if (in_array(strtolower($value->getValue()), ['false', 'null', 'undefined'])) {
                return '';
            }
        }

        return $this->pattern(
            'attribute_pattern',
            $this->format($key),
            $this->format($value)
        );
    }

    protected function formatPairTag($pattern, MarkupElement $element)
    {
        return sprintf(
            $pattern,
            $element->hasChildren()
                ? sprintf(
                    $this->isBlockTag($element)
                        ? $this->getNewLine().'%s'.$this->getIndent()
                        : '%s',
                    $this->formatElementChildren($element)
                )
                : ''
        );
    }

    protected function formatMarkupElement(MarkupElement $element)
    {
        $tag = $this->format($element->getName());
        $tagAndAttributes = $tag;
        foreach ($element->getAttributes() as $attribute) {
            $tagAndAttributes .= $this->format($attribute);
        }

        if ($this->isSelfClosingTag($element)) {
            return $this->pattern('self_closing_tag', $tagAndAttributes);
        }

        return sprintf(
            $this->isBlockTag($element)
                ? $this->getIndent().'%s'.$this->getNewLine()
                : '%s',
            $this->formatPairTag(
                (
                    $this->pattern('open_pair_tag', $tagAndAttributes).
                    '%s'.
                    $this->pattern('close_pair_tag', $tag)
                ),
                $element
            )
        );
    }
}
