<?php

namespace Phug\Formatter\Format;

use Phug\Formatter\MarkupInterface;
use Phug\FormatterException;

class HtmlFormat extends XmlFormat
{
    const DOCTYPE = '<!DOCTYPE html>';
    const SELF_CLOSING_TAG = '<%s>';
    const BOOLEAN_ATTRIBUTE_PATTERN = ' %s';

    public function __construct(array $options = null)
    {
        $this->setOptions([
            'inline_tags' => [
                'a',
                'abbr',
                'acronym',
                'b',
                'br',
                'code',
                'em',
                'font',
                'i',
                'img',
                'ins',
                'kbd',
                'map',
                'samp',
                'small',
                'span',
                'strong',
                'sub',
                'sup',
            ],
            'self_closing_tags' => [
                'area',
                'base',
                'br',
                'col',
                'command',
                'embed',
                'hr',
                'img',
                'input',
                'keygen',
                'link',
                'meta',
                'param',
                'source',
                'track',
                'wbr',
            ],
        ]);
        parent::__construct($options);
    }

    public function isSelfClosingTag(MarkupInterface $element)
    {
        $isSelfClosing = $element->belongsTo($this->getOption('self_closing_tags'));

        if ($isSelfClosing && $element->hasChildren()) {
            throw new FormatterException($element->getName().' is a self closing element: '.
                '<'.$element->getName().'/> but contains nested content.');
        }

        return $isSelfClosing;
    }

    public function isBlockTag(MarkupInterface $element)
    {
        return
            !$element->belongsTo($this->getOption('inline_tags')) &&
            (!$element->hasParent() || $this->isBlockTag($element->getParent()));
    }
}
