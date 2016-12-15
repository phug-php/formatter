<?php

namespace Phug\Formatter\Format;

use Phug\Formatter\AbstractFormat;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;

class XmlFormat extends AbstractFormat
{
    const DOCTYPE = '<?xml version="1.0" encoding="utf-8" ?>';
    const OPEN_PAIR_TAG = '<%s>';
    const CLOSE_PAIR_TAG = '</%s>';
    const SELF_CLOSING_TAG = '<%s />';
    const ATTRIBUTE_PATTERN = ' %s="%s"';
    const BOOLEAN_ATTRIBUTE_PATTERN = ' %s="%s"';

    public function __invoke(ElementInterface $element, $customDoctype = null)
    {

        return $this->getDoctype($customDoctype).
            $this->getNewLine().
            $this->format($element);
    }

    protected function getDoctype($customDoctype = null)
    {
        if ($customDoctype) {
            return sprintf('<!DOCTYPE %s>', $customDoctype);
        }

        return static::DOCTYPE;
    }

    protected function isSelfClosingTag(MarkupElement $element)
    {
        return !$element->hasChildren();
    }

    protected function isBlockTag(MarkupElement $element)
    {
        return true;
    }

    protected function formatAttributeElement(AttributeElement $element)
    {
        $value = $element->getItem();
        $key = $element->getKey();
        if ($value instanceof CodeElement) {
            if (strtolower($value->getValue()) === 'true') {
                $formattedKey = $this->format($key);

                return sprintf(static::BOOLEAN_ATTRIBUTE_PATTERN, $formattedKey, $formattedKey);
            }
            if (in_array(strtolower($value->getValue()), ['false', 'null', 'undefined'])) {
                return '';
            }
        }

        return sprintf(static::ATTRIBUTE_PATTERN, $this->format($key), $this->format($value));
    }

    protected function formatMarkupElement(MarkupElement $element)
    {
        $tag = $this->format($element->getName());
        $tagAndAttributes = $tag;
        foreach ($element->getAttributes() as $attribute) {
            $tagAndAttributes .= $this->format($attribute);
        }

        if ($this->isSelfClosingTag($element)) {
            return sprintf(static::SELF_CLOSING_TAG, $tagAndAttributes);
        }

        $content = $this->isBlockTag($element)
            ? $this->getNewIndentedLine()
            : '';
        $content .= sprintf(static::OPEN_PAIR_TAG, $tagAndAttributes);
        if ($element->hasChildren()) {
            $this->indentLevel++;
            $content .= implode('', array_map([$this, 'format'], $element->getChildren()));
            $this->indentLevel--;
            $content .= $this->getNewIndentedLine();
        }
        $content .= sprintf(static::CLOSE_PAIR_TAG, $tag);

        return $content;
    }
}
