<?php

namespace Phug\Test\Format;

use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Format\XmlFormat
 */
class XmlFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testFormatBooleanAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new CodeElement('true')));
        $htmlFormat = new XmlFormat();

        $this->assertSame('<?xml version="1.0" encoding="utf-8" ?><input type="checkbox" checked="checked" />', $htmlFormat($input));
    }
}
