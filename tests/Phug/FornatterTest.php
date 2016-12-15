<?php

namespace Phug\Test;

use Phug\Formatter;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;

/**
 * @coversDefaultClass \Phug\Formatter
 */
class FormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {

        $formatter = new Formatter(['foo' => 'bar']);

        $this->assertSame('bar', $formatter->getOption('foo'));
    }

    /**
     * @covers ::format
     */
    public function testFormat()
    {

        $formatter = new Formatter();

        $img = new MarkupElement('img');

        $this->assertSame('<!DOCTYPE html><img>', $formatter->format($img, HtmlFormat::class));

        $link = new MarkupElement('a');
        $format = new HtmlFormat();

        $this->assertSame('<!DOCTYPE html><a></a>', $formatter->format($link, $format));

        $link = new MarkupElement(new CodeElement('echo $tagName;'));

        $this->assertSame('<!DOCTYPE html><<?php echo $tagName; ?>></<?php echo $tagName; ?>>', $formatter->format($link, $format));
    }

    /**
     * @covers                   ::format
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Passed format handler needs to implement
     */
    public function testFormatWithWrongArgument()
    {

        $img = new MarkupElement('img');
        $formatter = new Formatter();

        $this->assertSame('<!DOCTYPE html><img>', $formatter->format($img, MarkupElement::class));
    }
}
