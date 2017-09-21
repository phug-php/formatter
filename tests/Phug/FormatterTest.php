<?php

namespace Phug\Test;

use InvalidArgumentException;
use Phug\DependencyException;
use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\CommentElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Formatter\Format\BasicFormat;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Format\XmlFormat;
use Phug\FormatterModuleInterface;
use Phug\Parser\Node\CodeNode;
use Phug\Parser\Node\ExpressionNode;
use Phug\Parser\Node\TextNode;
use Phug\Util\Exception\LocatedException;
use Phug\Util\SourceLocation;
use RuntimeException;

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
     * @covers ::__construct
     */
    public function testConstructorException()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Passed format class'.
            ' Phug\Formatter\Element\CodeElement'.
            ' must implement'.
            ' Phug\Formatter\FormatInterface'
        );
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
     * @covers ::setFormatHandler
     */
    public function testSetFormatHandlerException()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Passed format class'.
            ' Phug\Formatter\Element\CodeElement'.
            ' must implement'.
            ' Phug\Formatter\FormatInterface'
        );
        $formatter = new Formatter();
        $formatter->setFormatHandler('foo', CodeElement::class);
    }

    /**
     * @covers ::__construct
     */
    public function testDefaultFormatException()
    {
        $this->setExpectedException(
            RuntimeException::class,
            'Passed default format class'.
            ' Phug\Formatter\Element\CodeElement'.
            ' must implement'.
            ' Phug\Formatter\FormatInterface'
        );
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
     * @covers \Phug\Formatter\Element\MarkupElement::__construct
     * @covers \Phug\Formatter\Partial\HandleVariable::isInKeywordParams
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

        ob_start();
        $php = $formatter->format($link, $format);
        $tagName = 'section';
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<section></section>',
            $actual
        );

        $expression = new ExpressionElement('$tagName');
        $expression->uncheck();
        $link = new MarkupElement($expression);

        ob_start();
        $php = $formatter->format($link, $format);
        $tagName = false;
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<false></false>',
            $actual
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

        $exp = new ExpressionElement('foreach ($tabs as $key => $tab)');
        $code = $formatter->format($exp);

        self::assertRegExp('/as\s\$key\s=>\s\$tab/', $code);
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
     * @covers \Phug\Formatter\AbstractFormat::formatCode
     * @covers ::formatCode
     * @covers ::getFormatInstance
     */
    public function testFormatCodeMethod()
    {
        $formatter = new Formatter([
            'patterns' => [
                'transform_code' => '(%s)',
            ],
        ]);

        self::assertSame(
            '($foo = "bar")',
            $formatter->formatCode('$foo = "bar"')
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::pattern
     * @covers \Phug\Formatter\AbstractFormat::formatCode
     * @covers \Phug\Formatter\AbstractFormat::formatExpressionElement
     * @covers \Phug\Formatter\AbstractFormat::formatAttributeValueAccordingToName
     */
    public function testTransformExpression()
    {
        $formatter = new Formatter([
            'patterns' => [
                'transform_expression' => function ($expression) {
                    return preg_replace('/\.(?=\w)/', '->', $expression);
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
            '<?= (is_bool($_pug_temp = $foo->bar) ? var_export($_pug_temp, true) : $_pug_temp) ?>',
            $formatter->format($expression, HtmlFormat::class)
        );

        $expression->linkTo(new AttributeElement('a', 'a'));
        self::assertSame(
            '<?= (is_array($_pug_temp = $foo->bar) || is_object($_pug_temp) && '.
            '!method_exists($_pug_temp, "__toString") ? json_encode($_pug_temp) : strval($_pug_temp)) ?>',
            $formatter->format($expression, HtmlFormat::class)
        );

        $attribute = new AttributeElement('class', new ExpressionElement('$foo.bar'));

        ob_start();
        $foo = (object) [
            'bar' => ['gg', 'hh'],
        ];
        $php = $formatter->format($attribute, HtmlFormat::class);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            ' class="gg hh"',
            $actual
        );

        $attribute = new AttributeElement('style', new ExpressionElement('$foo.bar'));

        ob_start();
        $foo = (object) [
            'bar' => (object) [
                'color' => 'red',
            ],
        ];
        $php = $formatter->format($attribute, HtmlFormat::class);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            ' style="color:red"',
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
            '<?= (is_bool($_pug_temp = (isset($foo) ? $foo : \'\')) '.
            '? var_export($_pug_temp, true) : $_pug_temp) ?>',
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
            '(is_bool($_pug_temp = 42) ? var_export($_pug_temp, true) : $_pug_temp)',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer = new ExpressionElement('"<".$tag.">"');
        $answer->escape();
        $formatter = new Formatter();

        self::assertSame(
            '<?= htmlspecialchars((is_bool($_pug_temp = "<".(isset($tag) ? $tag : \'\').">") '.
            '? var_export($_pug_temp, true) : $_pug_temp)) ?>',
            $formatter->format($answer, HtmlFormat::class)
        );

        $answer->uncheck();
        $formatter = new Formatter();

        self::assertSame(
            '<?= htmlspecialchars((is_bool($_pug_temp = "<".$tag.">") '.
            '? var_export($_pug_temp, true) : $_pug_temp)) ?>',
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
     * @covers \Phug\Formatter\AbstractFormat::formatCommentElement
     */
    public function testIndent()
    {
        $foo = new MarkupElement('foo');
        $foo->appendChild(new MarkupElement('bar'));
        $foo->appendChild(new MarkupElement('biz'));
        $license = new MarkupElement('license');
        $license->appendChild(new MarkupElement('mit'));
        $license->appendChild(new CommentElement('gpl'));
        $foo->appendChild($license);
        $formatter = new Formatter();

        self::assertSame(
            '<foo><bar></bar><biz></biz><license><mit></mit><!-- gpl --></license></foo>',
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => true,
        ]);

        $expected = "<foo>\n  <bar></bar>\n  <biz></biz>\n".
            "  <license>\n    <mit></mit>\n    <!-- gpl -->\n  </license>\n</foo>\n";

        self::assertSame(
            str_replace("\n", PHP_EOL, $expected),
            $formatter->format($foo, HtmlFormat::class)
        );

        $formatter = new Formatter([
            'pretty' => "\t",
        ]);

        $expected = "<foo>\n\t<bar></bar>\n\t<biz></biz>\n".
            "\t<license>\n\t\t<mit></mit>\n\t\t<!-- gpl -->\n\t</license>\n</foo>\n";

        self::assertSame(
            str_replace("\n", PHP_EOL, $expected),
            $formatter->format($foo, HtmlFormat::class)
        );
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::formatCode
     * @covers \Phug\Formatter\AbstractFormat::formatCodeElement
     * @covers \Phug\Formatter\AbstractFormat::formatExpressionElement
     * @covers \Phug\Formatter\AbstractFormat::removePhpTokenHandler
     * @covers \Phug\Formatter\AbstractFormat::setPhpTokenHandler
     * @covers \Phug\Formatter\AbstractFormat::handleTokens
     * @covers \Phug\Formatter\Partial\PatternTrait::setPattern
     * @covers \Phug\Formatter\Partial\PatternTrait::setPatterns
     * @covers \Phug\Formatter\Partial\HandleVariable::isInFunctionParams
     * @covers \Phug\Formatter\Partial\HandleVariable::isInInterpolation
     * @covers \Phug\Formatter\Partial\HandleVariable::isInExclusionContext
     * @covers \Phug\Formatter\Partial\HandleVariable::handleVariable
     * @covers \Phug\Formatter\Element\CodeElement::getValueTokens
     * @covers \Phug\Formatter\Element\CodeElement::<public>
     */
    public function testFormatCode()
    {
        $bar = new ExpressionElement('$bar["x"]');
        $formatter = new Formatter();
        $format = new HtmlFormat($formatter);

        self::assertSame(
            '<?= (is_bool($_pug_temp = $bar["x"]) ? var_export($_pug_temp, true) : $_pug_temp) ?>',
            $formatter->format($bar, $format)
        );

        $bar = new ExpressionElement('$bar->x');

        self::assertSame(
            '<?= (is_bool($_pug_temp = $bar->x) ? var_export($_pug_temp, true) : $_pug_temp) ?>',
            $formatter->format($bar, $format)
        );

        $expression = new ExpressionElement("\$bar\n// comment\n->x");
        ob_start();
        $bar = (object) ['x' => 'X'];
        $php = $formatter->format($expression, $format);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            'X',
            $actual
        );

        $expression = new ExpressionElement("\$bar\n->x\n// comment");
        ob_start();
        $bar = (object) ['x' => 'X'];
        $php = $formatter->format($expression, $format);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            'X',
            $actual
        );

        ob_start();
        $foo = null;
        $php = $formatter->format(new ExpressionElement('$foo'), $format);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '',
            $actual
        );

        $format->removePhpTokenHandler(T_VARIABLE);

        ob_start();
        $foo = 'hello';
        $php = $formatter->format(new ExpressionElement('$foo'), $format);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            'hello',
            $actual
        );

        $format = new HtmlFormat($formatter);
        $format->setPatterns([
            'expression_in_text' => '%s',
        ]);
        $format->setPhpTokenHandler(T_VARIABLE, 'handle_variable(%s)');
        $foo = new ExpressionElement('$foo');

        self::assertSame(
            '<?= handle_variable($foo) ?>',
            $formatter->format($foo, $format)
        );

        $format = new HtmlFormat($formatter);
        $format->setPattern('expression_in_text', '%s');
        $format->setPhpTokenHandler(T_VARIABLE, 'handle_variable(%s)');
        $foo = new ExpressionElement('$foo');

        self::assertSame(
            '<?= handle_variable($foo) ?>',
            $formatter->format($foo, $format)
        );

        $formatter->setOption(['patterns', 'expression_in_text'], '%s');
        $format = new HtmlFormat($formatter);
        $format->setPhpTokenHandler(T_VARIABLE, 'handle_variable(%s)');
        $foo = new ExpressionElement('$foo');

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

        $if = new CodeElement('if (5 == 5)');
        $format = new HtmlFormat($formatter);

        self::assertSame(
            '<?php if (5 == 5) {} ?>',
            $formatter->format($if, $format)
        );

        $document = new DocumentElement();
        $if = new CodeElement('if (5 == 5) {');
        $if->appendChild(new MarkupElement('div'));
        $document->appendChild($if);
        $document->appendChild(new CodeElement('}'));
        $format = new HtmlFormat($formatter);

        self::assertSame(
            '<?php if (5 == 5) { ?><div></div><?php } ?>',
            str_replace('  ', ' ', $formatter->format($document, $format))
        );
    }

    /**
     * @group debug
     * @covers \Phug\Formatter\AbstractFormat::format
     * @covers \Phug\Formatter\AbstractFormat::formatElementChildren
     * @covers \Phug\Formatter::getSourceLine
     * @covers \Phug\Formatter::getDebugError
     * @covers \Phug\Formatter::fileContains
     */
    public function testFormatCodeWithDebug()
    {
        $formatter = new Formatter([
            'debug' => true,
        ]);
        $document = new DocumentElement();
        $createCodeElement = function ($content, $line) {
            $location = new SourceLocation('source.pug', $line, 0);
            $node = new CodeNode(null, $location);
            $node->setValue($content);

            return new CodeElement($content, $node);
        };
        $if = $createCodeElement('if (5 == 5)', 3);
        $if->appendChild($createCodeElement('$a = 5', 5));
        $else = $createCodeElement('else', 7);
        $else->appendChild($createCodeElement('$a = "?"', 9));
        $document->appendChild($if);
        $document->appendChild($else);
        $format = new HtmlFormat($formatter);

        self::assertTrue($format->getOption('debug'));
        self::assertSame(
            implode('', [
                "<?php \n// PUG_DEBUG".":1\n ?>",
                '<?php if (5 == 5) { ?>',
                "<?php \n// PUG_DEBUG".":0\n ?>",
                '<?php $a = 5 ?>',
                "<?php \n// PUG_DEBUG".":3\n",
                ' }  else { ?>',
                "<?php \n// PUG_DEBUG".":2\n ?>",
                '<?php $a = "?" ?>',
                '<?php } ?>',
            ]),
            $formatter->format($document, $format)
        );
        $exception = new \Exception();
        $error = $formatter->getDebugError($exception, 1);

        self::assertSame($error, $exception);

        include_once __DIR__.'/OpenThrowable.php';
        $exception = new OpenThrowable();
        $exception->setLine(5);
        $error = $formatter->getDebugError($exception, 1);

        self::assertSame($error, $exception);

        $formatter = new Formatter();
        $format = new HtmlFormat($formatter);
        $code = new CodeElement("\n".$format->getDebugComment(9999)."\n");

        self::assertSame(
            "<?php \n// PUG_DEBUG".":9999\n ?>",
            $formatter->format($code, $format)
        );

        $exception = new OpenThrowable();
        $exception->setLine(2);
        $error = $formatter->getDebugError($exception, 1);

        self::assertSame($error, $exception);

        $formatter = new Formatter([
            'debug' => true,
        ]);
        $document = new DocumentElement();
        $createCodeElement = function ($content, $line) {
            $location = new SourceLocation('source.pug', $line, 0);
            $node = new CodeNode(null, $location);
            $node->setValue($content);

            return new CodeElement($content, $node);
        };
        $if = $createCodeElement('if (5 == 5)', 3);
        $if->appendChild($createCodeElement('$a = 5', 5));
        $else = $createCodeElement('else', 7);
        $location = new SourceLocation('source.pug', 9, 0);
        $node = new TextNode(null, $location);
        $node->setValue("\nfoo\n");
        $else->appendChild(new TextElement("\nfoo\n", $node));
        $else->appendChild($createCodeElement('$a = "?"', 11));
        $document->appendChild($if);
        $document->appendChild($else);
        $format = new HtmlFormat($formatter);

        self::assertTrue($format->getOption('debug'));
        self::assertSame(
            implode('', [
                "<?php \n// PUG_DEBUG".":1\n ?>",
                '<?php if (5 == 5) { ?>',
                "<?php \n// PUG_DEBUG".":0\n ?>",
                '<?php $a = 5 ?>',
                "<?php \n// PUG_DEBUG".":4\n",
                ' }  else { ?>',
                "<?php \n// PUG_DEBUG".":2\n ?>\n",
                "\nfoo\n",
                "<?php \n// PUG_DEBUG".":3\n ?>",
                '<?php $a = "?" ?>',
                '<?php } ?>',
            ]),
            $formatter->format($document, $format)
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
            "<p>Foo\nbar</p>",
            $formatter->format($document, $format)
        );
    }

    /**
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

        $formatter = new Formatter(['dependencies_storage' => 'dep']);

        self::assertSame(0, $formatter->getDependencies()->countRequiredDependencies());

        self::assertSame('', $formatter->formatDependencies());

        $message = null;

        try {
            $formatter->getDependencies()->setAsRequired('bar');
        } catch (DependencyException $exception) {
            $message = $exception->getMessage();
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
            'on_dependency_storage'       => function (Formatter\Event\DependencyStorageEvent $event) {
                $event->setDependencyStorage(mb_substr(ltrim($event->getDependencyStorage()), 1));
            },
        ]);

        self::assertSame('dep[\'foo\']', $formatter->getDependencyStorage('foo'));
    }

    /**
     * @covers \Phug\Formatter::__construct
     * @covers \Phug\Formatter::storeDebugNode
     * @covers \Phug\Formatter::fileContains
     * @covers \Phug\Formatter::getSourceLine
     * @covers \Phug\Formatter::debugIdExists
     * @covers \Phug\Formatter::getNodeFromDebugId
     * @covers \Phug\Formatter::getDebugError
     * @covers \Phug\Formatter\AbstractFormat::__construct
     * @covers \Phug\Formatter\AbstractFormat::getDebugComment
     * @covers \Phug\Formatter\AbstractFormat::getDebugInfo
     * @covers \Phug\Formatter\AbstractElement::getOriginNode
     */
    public function testDebugError()
    {
        $formatter = new Formatter([
            'debug' => true,
        ]);
        $node = new ExpressionNode(null, new SourceLocation('source.pug', 3, 15));
        $document = new DocumentElement();
        $document->appendChild($htmlEl = new MarkupElement('html'));
        $htmlEl->appendChild($bodyEl = new MarkupElement('body'));
        $bodyEl->appendChild(new ExpressionElement(
            '12 / 0',
            $node
        ));
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        $error = null;
        ob_start();

        try {
            eval('?>'.$php);
        } catch (\Exception $exception) {
            /** @var LocatedException $error */
            $error = $formatter->getDebugError($exception, $php);
        }
        ob_end_clean();

        self::assertInstanceOf(LocatedException::class, $error);
        self::assertSame(3, $error->getLocation()->getLine());
        self::assertSame(15, $error->getLocation()->getOffset());
        self::assertSame('source.pug', $error->getLocation()->getPath());

        $formatter = new Formatter([
            'debug' => true,
        ]);
        $helper = function () {
            return 12 / 0;
        };
        $node = new ExpressionNode(null, new SourceLocation(null, 7, 9));
        $document = new DocumentElement();
        $document->appendChild($htmlEl = new MarkupElement('html'));
        $htmlEl->appendChild($bodyEl = new MarkupElement('body'));
        $bodyEl->appendChild(new ExpressionElement(
            'call_user_func($helper)',
            $node
        ));
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        if (defined('HHVM_VERSION')) {
            return;
        }

        $error = null;
        ob_start();
        call_user_func(function ($code) use (&$error, $helper, $formatter) {
            try {
                eval($code);
            } catch (\Exception $exception) {
                /** @var LocatedException $error */
                $error = $formatter->getDebugError($exception, $code);
            }
        }, '?>'.$php);
        ob_end_clean();

        self::assertInstanceOf(LocatedException::class, $error);
        self::assertSame(7, $error->getLocation()->getLine());
        self::assertSame(9, $error->getLocation()->getOffset());
        self::assertSame(null, $error->getLocation()->getPath());

        $error = null;
        $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test-'.mt_rand(0, 9999999);
        file_put_contents($file, $php);
        ob_start();
        call_user_func(function () use ($file, &$error, $helper, $formatter) {
            try {
                include $file;
            } catch (\Exception $exception) {
                /** @var LocatedException $error */
                $error = $formatter->getDebugError($exception, file_get_contents($file));
            }
        });
        ob_end_clean();

        self::assertInstanceOf(LocatedException::class, $error);
        self::assertSame(7, $error->getLocation()->getLine());
        self::assertSame(9, $error->getLocation()->getOffset());
        self::assertSame(null, $error->getLocation()->getPath());
    }

    /**
     * @covers ::getModuleBaseClassName
     */
    public function testGetModuleBaseClassName()
    {
        self::assertSame(FormatterModuleInterface::class, (new Formatter())->getModuleBaseClassName());
    }
}
