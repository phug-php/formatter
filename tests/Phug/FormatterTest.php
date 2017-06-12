<?php

namespace Phug\Test;

use Phug\DependencyException;
use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Formatter\Format\BasicFormat;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Format\XmlFormat;

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
     * @covers                   ::__construct
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Passed format class
     * @expectedExceptionMessage Phug\Formatter\Element\CodeElement
     * @expectedExceptionMessage must implement
     * @expectedExceptionMessage Phug\Formatter\FormaterInterface
     */
    public function testConstructorException()
    {
        $formatter = new Formatter([
            'formats' => [
                'html' => CodeElement::class,
            ],
        ]);
    }

    /**
     * @covers ::setFormatHandler
     */
    public function testSetFormatHandler()
    {
        $formatter = new Formatter();
        $formatter->setFormatHandler('foo', HtmlFormat::class);

        self::assertSame(HtmlFormat::class, $formatter->getOption(['formats', 'foo']));
    }

    /**
     * @covers                   ::setFormatHandler
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Passed format class
     * @expectedExceptionMessage Phug\Formatter\Element\CodeElement
     * @expectedExceptionMessage must implement
     * @expectedExceptionMessage Phug\Formatter\FormaterInterface
     */
    public function testSetFormatHandlerException()
    {
        $formatter = new Formatter();
        $formatter->setFormatHandler('foo', CodeElement::class);
    }

    /**
     * @covers                   ::__construct
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Passed default format class
     * @expectedExceptionMessage Phug\Formatter\Element\CodeElement
     * @expectedExceptionMessage must implement
     * @expectedExceptionMessage Phug\Formatter\FormaterInterface
     */
    public function testDefaultFormatException()
    {
        new Formatter([
            'default_format' => CodeElement::class,
        ]);
    }

    /**
     * @covers ::format
     * @covers ::setFormat
     * @covers ::getFormat
     */
    public function testSetFormat()
    {
        $formatter = new Formatter();
        $img = new MarkupElement('img', true);

        $formatter->setFormat('html');

        self::assertSame(HtmlFormat::class, $formatter->getFormat());
        self::assertSame(
            '<img/>',
            $formatter->format($img)
        );

        $formatter->setFormat('xml');

        self::assertSame(XmlFormat::class, $formatter->getFormat());
        self::assertSame(
            '<img />',
            $formatter->format($img)
        );

        $formatter->setFormat('doesnotexists');

        self::assertSame(BasicFormat::class, $formatter->getFormat());
        self::assertSame(
            '<img />',
            $formatter->format($img)
        );

        self::assertSame('<?xml version="1.0" encoding="utf-8" ?>', $formatter->format(new DoctypeElement('xml')));

        self::assertSame(XmlFormat::class, $formatter->getFormat());

        self::assertSame('<!DOCTYPE doesnotexists>', $formatter->format(new DoctypeElement('doesnotexists')));

        self::assertSame(BasicFormat::class, $formatter->getFormat());
    }

    /**
     * @covers ::format
     * @covers \Phug\Formatter\AbstractFormat::__construct
     * @covers \Phug\Formatter\AbstractFormat::handleTokens
     * @covers \Phug\Formatter\AbstractFormat::formatCodeElement
     * @covers \Phug\Formatter\Partial\HandleVariable::isInFunctionParams
     * @covers \Phug\Formatter\Partial\HandleVariable::isInInterpolation
     * @covers \Phug\Formatter\Partial\HandleVariable::isInExclusionContext
     * @covers \Phug\Formatter\Partial\HandleVariable::handleVariable
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
        $format = new HtmlFormat($formatter);

        self::assertSame(
            '<a></a>',
            $formatter->format($link, $format)
        );

        $link = new MarkupElement(new ExpressionElement('$tagName'));

        self::assertSame(
            '<<?= (isset($tagName) ? $tagName : \'\') ?>></<?= (isset($tagName) ? $tagName : \'\') ?>>',
            $formatter->format($link, $format)
        );

        $expression = new ExpressionElement('$tagName');
        $expression->uncheck();
        $link = new MarkupElement($expression);

        self::assertSame(
            '<<?= $tagName ?>></<?= $tagName ?>>',
            $formatter->format($link, $format)
        );

        $exp = new ExpressionElement('"foo.$ext"');
        $formatter = new Formatter();
        $return = eval(str_replace(['<?=', '?>'], ['return', ';'], $formatter->format($exp)));

        self::assertSame('foo.', $return);

        $exp = new ExpressionElement('"foo.$ext"');
        $return = eval(str_replace(['<?=', '?>'], ['return', ';'], '$ext = "bar";'.$formatter->format($exp)));

        self::assertSame('foo.bar', $return);

        $exp = new ExpressionElement('($a = function ($a) { return $a; }) ? call_user_func($a, "A") : null');
        $return = eval(str_replace(['<?=', '?>'], ['return', ';'], $formatter->format($exp)));

        self::assertSame('A', $return);

        $exp = new ExpressionElement('($a = function ($a, $b) { return $c; }) ? call_user_func($a, "A", "B") : null');
        $return = eval(str_replace(['<?=', '?>'], ['return', ';'], $formatter->format($exp)));

        self::assertSame('', $return);
    }

    /**
     * @covers                   ::format
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Arguments miss one of the Phug\Formatter\ElementInterface type
     */
    public function testFormatWithWrongArgument()
    {
        $formatter = new Formatter();
        $formatter->format(HtmlFormat::class, HtmlFormat::class);
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::pattern
     * @covers \Phug\Formatter\AbstractFormat::formatCodeElement
     */
    public function testFormatCodeElement()
    {
        $answer = new CodeElement('42');
        $formatter = new Formatter([
            'patterns' => [
                'php_handle_code' => '%s * 2',
            ],
        ]);

        self::assertSame(
            '42 * 2',
            $formatter->format($answer, HtmlFormat::class)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::handleCode
     * @covers ::handleCode
     * @covers ::getFormatInstance
     */
    public function testHandleCode()
    {
        $formatter = new Formatter();

        self::assertSame(
            '<?php $foo = "bar"; ?>',
            $formatter->handleCode('$foo = "bar";')
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::pattern
     * @covers \Phug\Formatter\AbstractFormat::formatCode
     */
    public function testTransformExpression()
    {
        $formatter = new Formatter([
            'patterns' => [
                'transform_expression' => function ($expression) {
                    return str_replace('.', '->', $expression);
                },
            ],
        ]);

        $code = new CodeElement('$bar = $foo.bar');
        self::assertSame(
            '<?php $bar = $foo->bar ?>',
            $formatter->format($code, HtmlFormat::class)
        );

        $expression = new ExpressionElement('$foo.bar');
        self::assertSame(
            '<?= $foo->bar ?>',
            $formatter->format($expression, HtmlFormat::class)
        );

        $expression = new AttributeElement('class', new ExpressionElement('$foo.bar'));

        ob_start();
        $foo = (object) [
            'bar' => ['gg', 'hh'],
        ];
        $php = $formatter->format($expression, HtmlFormat::class);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            ' class="gg hh"',
            $actual
        );

        $formatter = new Formatter([
            'patterns' => [
                'transform_expression' => function ($expression) {
                    return '$'.$expression;
                },
            ],
        ]);

        $expression = new ExpressionElement('foo');
        self::assertSame(
            '<?= (isset($foo) ? $foo : \'\') ?>',
            $formatter->format($expression, HtmlFormat::class)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::pattern
     * @covers \Phug\Formatter\AbstractFormat::formatAttributeValueAccordingToName
     * @covers \Phug\Formatter\AbstractFormat::formatExpressionElement
     */
    public function testExpressionElement()
    {
        $answer = new ExpressionElement('call_me()');
        $formatter = new Formatter([
            'patterns' => [
                'php_display_code' => function ($string) {
                    return str_replace('call_me()', '42', $string);
                },
            ],
        ]);

        self::assertSame(
            '42',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer = new ExpressionElement('"<".$tag.">"');
        $answer->escape();
        $formatter = new Formatter();

        self::assertSame(
            '<?= htmlspecialchars("<".(isset($tag) ? $tag : \'\').">") ?>',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer->uncheck();
        $formatter = new Formatter();

        self::assertSame(
            '<?= htmlspecialchars("<".$tag.">") ?>',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer = new ExpressionElement('"<div>"');
        $formatter = new Formatter();

        self::assertSame(
            '<div>',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer->escape();
        $formatter = new Formatter();

        self::assertSame(
            '&lt;div&gt;',
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

        $expected = "<foo>\n  <bar></bar>\n  <biz></biz>\n".
            "  <license>\n    <mit></mit>\n  </license>\n</foo>\n";

        self::assertSame(
            str_replace("\n", PHP_EOL, $expected),
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => "\t",
        ]);

        $expected = "<foo>\n\t<bar></bar>\n\t<biz></biz>\n".
            "\t<license>\n\t\t<mit></mit>\n\t</license>\n</foo>\n";

        self::assertSame(
            str_replace("\n", PHP_EOL, $expected),
            $formatter->format($foo, HtmlFormat::class)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::formatCode
     * @covers \Phug\Formatter\AbstractFormat::formatCodeElement
     * @covers \Phug\Formatter\AbstractFormat::removePhpTokenHandler
     * @covers \Phug\Formatter\AbstractFormat::setPhpTokenHandler
     * @covers \Phug\Formatter\AbstractFormat::handleTokens
     * @covers \Phug\Formatter\Partial\HandleVariable::isInFunctionParams
     * @covers \Phug\Formatter\Partial\HandleVariable::isInInterpolation
     * @covers \Phug\Formatter\Partial\HandleVariable::isInExclusionContext
     * @covers \Phug\Formatter\Partial\HandleVariable::handleVariable
     */
    public function testFormatCode()
    {
        $foo = new ExpressionElement('$foo');
        $bar = new ExpressionElement('$bar["x"]');
        $formatter = new Formatter();
        $format = new HtmlFormat($formatter);

        self::assertSame(
            '<?= $bar["x"] ?>',
            $formatter->format($bar, $format)
        );

        $bar = new ExpressionElement('$bar->x');

        self::assertSame(
            '<?= $bar->x ?>',
            $formatter->format($bar, $format)
        );

        $bar = new ExpressionElement("\$bar\n// comment\n->x");

        self::assertSame(
            "<?= \$bar\n// comment\n->x ?>",
            $formatter->format($bar, $format)
        );

        self::assertSame(
            '<?= (isset($foo) ? $foo : \'\') ?>',
            $formatter->format($foo, $format)
        );

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

        $if = new CodeElement('if (5 == 5)');
        $if->appendChild(new MarkupElement('div'));
        $format = new HtmlFormat($formatter);

        self::assertSame(
            '<?php if (5 == 5) { ?><div></div><?php } ?>',
            $formatter->format($if, $format)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::formatTextElement
     */
    public function testFormatTextElement()
    {
        $text = new TextElement('Hello <b>World</b>!');
        $formatter = new Formatter();
        $format = new HtmlFormat($formatter);

        self::assertSame(
            'Hello <b>World</b>!',
            $formatter->format($text, $format)
        );

        $text->escape();
        $formatter = new Formatter();
        $format = new HtmlFormat($formatter);

        self::assertSame(
            'Hello &lt;b&gt;World&lt;/b&gt;!',
            $formatter->format($text, $format)
        );

        $document = new DocumentElement();
        $paragraph = new MarkupElement('p');
        $paragraph->appendChild(new TextElement('Foo'));
        $paragraph->appendChild(new TextElement('bar'));
        $document->appendChild($paragraph);

        self::assertSame(
            '<p>Foo bar</p>',
            $formatter->format($document, $format)
        );
    }

    /**
     * @covers ::initDependencies
     * @covers ::formatDependencies
     * @covers ::getDependencies
     * @covers ::getDependencyStorage
     */
    public function testDependencies()
    {
        $formatter = new Formatter(['dependencies_storage' => 'dep']);
        $formatter->getDependencies()->register('bar', 42);

        self::assertSame('$dep[\'foo\']', $formatter->getDependencyStorage('foo'));

        self::assertSame(0, $formatter->getDependencies()->countRequiredDependencies());

        $formatter->getDependencies()->setAsRequired('bar');

        self::assertSame(1, $formatter->getDependencies()->countRequiredDependencies());

        $formatter->initDependencies();

        self::assertSame(0, $formatter->getDependencies()->countRequiredDependencies());

        self::assertSame('', $formatter->formatDependencies());

        $message = null;
        try {
            $formatter->getDependencies()->setAsRequired('bar');
        } catch (DependencyException $e) {
            $message = $e->getMessage();
        }

        self::assertSame('bar dependency not found.', $message);

        $formatter->getDependencies()->register('bar', 42);
        $formatter->getDependencies()->setAsRequired('bar');

        self::assertSame(1, $formatter->getDependencies()->countRequiredDependencies());

        self::assertSame('<?php $dep = ['.PHP_EOL.
            '  \'bar\' => 42,'.PHP_EOL.
            ']; ?>', $formatter->formatDependencies());

        $formatter = new Formatter([
            'dependencies_storage'        => 'dep',
            'dependencies_storage_getter' => function ($php) {
                return substr(ltrim($php), 1);
            },
        ]);

        self::assertSame('dep[\'foo\']', $formatter->getDependencyStorage('foo'));
    }
}
