<?php

namespace Phug\Test;

use Phug\Formatter;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\HtmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter
 */
class FornatterTest extends \PHPUnit_Framework_TestCase
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
            '<img>',
            $formatter->format($img, HtmlFormat::class)
        );

        $link = new MarkupElement('a');
        $format = new HtmlFormat();

        self::assertSame(
            '<a></a>',
            $formatter->format($link, $format)
        );

        $link = new MarkupElement(new ExpressionElement('$tagName'));

        self::assertSame(
            '<<?= isset($tagName) ? $tagName : \'\' ?>></<?= isset($tagName) ? $tagName : \'\' ?>>',
            $formatter->format($link, $format)
        );
    }

    /**
     * @covers                   ::format
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Arguments miss one of the Phug\Formatter\FormatInterface type
     */
    public function testFormatWithWrongArgument()
    {
        $img = new MarkupElement('img');
        $formatter = new Formatter();

        self::assertSame(
            '<img>',
            $formatter->format($img, MarkupElement::class)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::pattern
     * @covers \Phug\Formatter\AbstractFormat::formatCodeElement
     */
    public function testFormatCodeElement()
    {
        $answer = new CodeElement('42');
        $formatter = new Formatter([
            'php_handle_code' => '%s * 2',
        ]);

        self::assertSame(
            '42 * 2',
            $formatter->format($answer, HtmlFormat::class)
        );
    }

    /**
     * @group exp
     * @covers \Phug\Formatter\AbstractFormat::pattern
     * @covers \Phug\Formatter\AbstractFormat::formatExpressionElement
     */
    public function testExpressionElement()
    {
        $answer = new ExpressionElement('42');
        $formatter = new Formatter([
            'php_display_code' => function ($string) {
                return strval(intval($string) * 2);
            },
        ]);

        self::assertSame(
            '84',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer = new ExpressionElement('42');
        $answer->escape();
        $formatter = new Formatter();

        self::assertSame(
            '<?= htmlspecialchars(42) ?>',
            $formatter->format($answer, HtmlFormat::class)
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
            '<foo><bar></bar><biz></biz><license><mit></mit></license></foo>',
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => true,
        ]);

        self::assertSame(
            "<foo>\n  <bar></bar>\n  <biz></biz>\n  <license>\n    <mit></mit>\n  </license>\n</foo>\n",
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => "\t",
        ]);

        self::assertSame(
            "<foo>\n\t<bar></bar>\n\t<biz></biz>\n\t<license>\n\t\t<mit></mit>\n\t</license>\n</foo>\n",
            $formatter->format($foo, HtmlFormat::class)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::formatCode
     * @covers \Phug\Formatter\AbstractFormat::removePhpTokenHandler
     * @covers \Phug\Formatter\AbstractFormat::setPhpTokenHandler
     */
    public function testFormatCode()
    {
        $foo = new ExpressionElement('$foo');
        $formatter = new Formatter();
        $format = new HtmlFormat();
        $format->removePhpTokenHandler(T_VARIABLE);

        self::assertSame(
            '<?= $foo ?>',
            $formatter->format($foo, $format)
        );

        $format->setPhpTokenHandler(T_VARIABLE, 'handle_variable(%s)');

        self::assertSame(
            '<?= handle_variable($foo) ?>',
            $formatter->format($foo, $format)
        );

        $foo = new ExpressionElement('foo(4 + (5 * 2))');
        $format->setPhpTokenHandler('(', '(handle_parenthesis(');
        $format->setPhpTokenHandler(')', '))');

        self::assertSame(
            '<?= foo(handle_parenthesis(4 + (handle_parenthesis(5 * 2)))) ?>',
            $formatter->format($foo, $format)
        );
    }
}
