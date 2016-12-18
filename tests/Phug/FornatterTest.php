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

        self::assertSame('bar', $formatter->getOption('foo'));
    }

    /**
     * @covers ::format
     * @covers \Phug\Formatter\AbstractFormat::formatCodeElement
     */
    public function testFormat()
    {

        $formatter = new Formatter();

        $img = new MarkupElement('img');

        self::assertSame(
            '<!DOCTYPE html><img>',
            $formatter->format($img, HtmlFormat::class)
        );

        $link = new MarkupElement('a');
        $format = new HtmlFormat();

        self::assertSame(
            '<!DOCTYPE html><a></a>',
            $formatter->format($link, $format)
        );

        $link = new MarkupElement(new CodeElement('$tagName'));

        self::assertSame(
            '<!DOCTYPE html><<?php echo $tagName; ?>></<?php echo $tagName; ?>>',
            $formatter->format($link, $format)
        );
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

        self::assertSame(
            '<!DOCTYPE html><img>',
            $formatter->format($img, MarkupElement::class)
        );
    }

    /**
    * @covers \Phug\Formatter\AbstractFormat::getNewLine
    * @covers \Phug\Formatter\AbstractFormat::getIndent
     */
    public function testIndent()
    {

        $foo = new MarkupElement('foo');
        $foo->appendChild(new MarkupElement('bar'));
        $foo->appendChild(new MarkupElement('biz'));
        $license = new MarkupElement('license');
        $license->appendChild(new MarkupElement('mit'));
        $foo->appendChild($license);
        $formatter = new Formatter();

        self::assertSame(
            '<!DOCTYPE html><foo><bar></bar><biz></biz><license><mit></mit></license></foo>',
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => true,
        ]);

        self::assertSame(
            "<!DOCTYPE html>\n<foo>\n  <bar></bar>\n  <biz></biz>\n  <license>\n    <mit></mit>\n  </license>\n</foo>\n",
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => "\t",
        ]);

        self::assertSame(
            "<!DOCTYPE html>\n<foo>\n\t<bar></bar>\n\t<biz></biz>\n\t<license>\n\t\t<mit></mit>\n\t</license>\n</foo>\n",
            $formatter->format($foo, HtmlFormat::class)
        );
    }
}
