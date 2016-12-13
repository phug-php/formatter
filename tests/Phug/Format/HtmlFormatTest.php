<?php

namespace Phug\Test\Format;

use Phug\Formatter\ElementInterface;
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

        $img = new MarkupElement('img', ['src' => '/foo/bar.png']);
        $htmlFormat = new HtmlFormat();

        $this->assertSame('<img>', $htmlFormat($img));
    }

    /**
     * @covers ::<public>
     */
    public function testCustomFormatHandler()
    {

        $img = new MarkupElement('img', ['src' => '/foo/bar.png']);
        $htmlFormat = new HtmlFormat();
        $htmlFormat->setElementHandler(MarkupElement::class, function (ElementInterface $element) {
            return strtoupper($element->getTagName());
        });

        $this->assertSame('IMG', $htmlFormat($img));
    }

    /**
     * @covers ::<public>
     */
    public function testMissingFormatHandler()
    {

        $img = new MarkupElement('img', ['src' => '/foo/bar.png']);
        $htmlFormat = new HtmlFormat();
        $htmlFormat->removeElementHandler(MarkupElement::class);

        $this->assertSame('', $htmlFormat($img));
    }
}
