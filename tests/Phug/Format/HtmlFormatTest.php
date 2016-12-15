<?php

namespace Phug\Test\Format;

use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\HtmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Format\HtmlFormat
 */
class HtmlFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testHtmlFormat()
    {

        $img = new MarkupElement('img');
        $htmlFormat = new HtmlFormat();

        $this->assertSame('<!DOCTYPE html><img>', $htmlFormat($img));
    }

    /**
     * @covers ::<public>
     */
    public function testCustomFormatHandler()
    {

        $img = new MarkupElement('img');
        $htmlFormat = new HtmlFormat();
        $htmlFormat->setElementHandler(MarkupElement::class, function (ElementInterface $element) {
            return strtoupper($element->getName());
        });

        $this->assertSame('<!DOCTYPE html>IMG', $htmlFormat($img));
    }

    /**
     * @covers ::<public>
     */
    public function testMissingFormatHandler()
    {

        $img = new MarkupElement('img');
        $htmlFormat = new HtmlFormat();
        $htmlFormat->removeElementHandler(MarkupElement::class);

        $this->assertSame('<!DOCTYPE html>', $htmlFormat($img));
    }

    /**
     * @covers ::<public>
     */
    public function testFormatSingleTagWithAttributes()
    {

        $img = new MarkupElement('img');
        $img->getAttributes()->attach(new AttributeElement('src', 'foo.png'));
        $htmlFormat = new HtmlFormat();

        $this->assertSame('<!DOCTYPE html><img src="foo.png">', $htmlFormat($img));
    }

    /**
     * @covers ::<public>
     */
    public function testFormatBooleanAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new CodeElement('true')));
        $htmlFormat = new HtmlFormat();

        $this->assertSame('<!DOCTYPE html><input type="checkbox" checked>', $htmlFormat($input));
    }

    /**
     * @covers                   ::isBlockTag
     * @expectedException        \Phug\FormatterException
     * @expectedExceptionMessage input is a self closing element: <input/> but contains nested content.
     */
    public function testChildrenInInlineTag()
    {

        $input = new MarkupElement('input');
        $input->appendChild(new MarkupElement('i'));
        $htmlFormat = new HtmlFormat();
        $htmlFormat($input);
    }
}
