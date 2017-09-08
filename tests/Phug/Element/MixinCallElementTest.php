<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\MixinCallElement;
use Phug\Formatter\Element\MixinElement;
use Phug\Formatter\Element\TextElement;
use SplObjectStorage;

/**
 * @coversDefaultClass \Phug\Formatter\Element\MixinCallElement
 */
class MixinCallElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Phug\Formatter::getMixins
     * @covers \Phug\Formatter::requireMixin
     * @covers \Phug\Formatter::formatDependencies
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
     * @covers \Phug\Formatter\AbstractFormat::getMixinAttributes
     * @covers \Phug\Formatter\AbstractFormat::formatMixinAttributeValue
     * @covers \Phug\Formatter\AbstractFormat::formatMixinCallElement
     * @covers ::<public>
     */
    public function testMixinCallElement()
    {
        $document = new DocumentElement();
        $document->appendChild(new CodeElement('$test = "Hello"'));

        $mixin = new MixinElement();
        $mixin->setName('tabs');
        $tabs = new AttributeElement('tabs', null);
        $tabs->setIsVariadic(true);
        $mixin->getAttributes()->attach($tabs);
        $div = new MarkupElement('div');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('$attributes'));
        $assignment = new AssignmentElement('attributes', $data, $div);
        $div->getAssignments()->attach($assignment);
        $expression = new ExpressionElement('$__pug_children(get_defined_vars())');
        $expression->uncheck();
        $expression->preventFromTransformation();
        $div->appendChild($expression);
        $mixin->appendChild($div);
        $document->appendChild($mixin);

        $mixinCall = new MixinCallElement();
        $mixinCall->setName('tabs');
        $attributes = new AttributeElement('bar', 'bar');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["foo" => "Foo"]'));
        $assignment = new AssignmentElement('attributes', $data, $mixinCall);
        $mixinCall->getAssignments()->attach($assignment);
        $mixinCall->getAttributes()->attach($attributes);
        $mixinCall->appendChild(new ExpressionElement('$test'));
        $document->appendChild($mixinCall);

        $formatter = new Formatter();
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        ob_start();
        call_user_func(function ($__php) {
            eval('?>'.$__php);
        }, $php);
        $html = ob_get_contents();
        ob_end_clean();

        self::assertSame('<div bar="bar" foo="Foo">Hello</div>', $html);
    }

    /**
     * @covers \Phug\Formatter::getMixins
     * @covers \Phug\Formatter::requireMixin
     * @covers \Phug\Formatter::formatDependencies
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
     * @covers \Phug\Formatter\AbstractFormat::formatMixinAttributeValue
     * @covers \Phug\Formatter\AbstractFormat::getMixinAttributes
     * @covers \Phug\Formatter\AbstractFormat::formatMixinCallElement
     * @covers ::<public>
     */
    public function testDefaultValue()
    {
        $document = new DocumentElement();

        $mixin = new MixinElement();
        $mixin->setName('test');
        $mixin->getAttributes()->attach(new AttributeElement(
            'foo',
            new ExpressionElement('"Foo"')
        ));
        $mixin->getAttributes()->attach(new AttributeElement(
            'bar',
            new ExpressionElement('"Bar"')
        ));
        $div = new MarkupElement('div');
        $div->appendChild(new ExpressionElement('$foo'));
        $div->appendChild(new ExpressionElement('$bar'));
        $mixin->appendChild($div);
        $document->appendChild($mixin);

        $mixinCall = new MixinCallElement();
        $mixinCall->setName('test');
        $attributes = new AttributeElement(null, new ExpressionElement('"Baz"'));
        $mixinCall->getAttributes()->attach($attributes);
        $document->appendChild($mixinCall);

        $formatter = new Formatter();
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        ob_start();
        call_user_func(function ($__php) {
            eval('?>'.$__php);
        }, $php);
        $html = ob_get_contents();
        ob_end_clean();

        self::assertSame('<div>BazBar</div>', $html);
    }

    /**
     * @covers \Phug\Formatter::getMixins
     * @covers \Phug\Formatter::requireMixin
     * @covers \Phug\Formatter::formatDependencies
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
     * @covers \Phug\Formatter\AbstractFormat::formatMixinAttributeValue
     * @covers \Phug\Formatter\AbstractFormat::getMixinAttributes
     * @covers \Phug\Formatter\AbstractFormat::formatMixinCallElement
     * @covers ::<public>
     */
    public function testDynamicCall()
    {
        $document = new DocumentElement();

        $mixin = new MixinElement();
        $mixin->setName('test');
        $mixin->getAttributes()->attach(new AttributeElement(
            'foo',
            new TextElement('Foo')
        ));
        $mixin->getAttributes()->attach(new AttributeElement(
            'bar',
            new TextElement('Bar')
        ));
        $div = new MarkupElement('div');
        $div->appendChild(new ExpressionElement('$foo'));
        $div->appendChild(new ExpressionElement('$bar'));
        $mixin->appendChild($div);
        $document->appendChild($mixin);

        $mixinCall = new MixinCallElement();
        $mixinCall->setName(new ExpressionElement('$mixinName'));
        $attributes = new AttributeElement(null, new ExpressionElement('"Baz"'));
        $mixinCall->getAttributes()->attach($attributes);
        $document->appendChild($mixinCall);

        $formatter = new Formatter();
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        ob_start();
        call_user_func(function ($__php) {
            $mixinName = 'test';
            eval('?>'.$__php);
        }, $php);
        $html = ob_get_contents();
        ob_end_clean();

        self::assertSame('<div>BazBar</div>', $html);
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\AbstractFormat::formatMixinCallElement
     */
    public function testUnknownMixinDebugOn()
    {
        $document = new DocumentElement();
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('undef');
        $document->appendChild($mixinCall);

        $formatter = new Formatter([
            'debug' => true,
        ]);
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;
        $message = null;

        ob_start();

        try {
            call_user_func(function ($__php) {
                eval('?>'.$__php);
            }, $php);
        } catch (\InvalidArgumentException $exception) {
            $message = $exception->getMessage();
        }
        ob_end_clean();

        self::assertSame('Unknown undef mixin called.', $message);
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\AbstractFormat::formatMixinCallElement
     */
    public function testUnknownMixinDebugOff()
    {
        $document = new DocumentElement();
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('undef');
        $document->appendChild($mixinCall);
        $document->appendChild(new TextElement('next'));

        $formatter = new Formatter([
            'debug' => false,
        ]);
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        ob_start();
        call_user_func(function ($__php) {
            eval('?>'.$__php);
        }, $php);
        $html = ob_get_contents();
        ob_end_clean();

        self::assertSame('next', $html);
    }

    /**
     * @covers \Phug\Formatter::getDestructors
     * @covers \Phug\Formatter\AbstractFormat::getChildrenIterator
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
     */
    public function testPhpUnwrap()
    {
        $document = new DocumentElement();
        $mixin = new MixinElement();
        $mixin->setName('foo');
        $mixin->appendChild(new CodeElement('echo 1'));
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('foo');
        $document->appendChild($mixin);
        $document->appendChild($mixinCall);

        $formatter = new Formatter([
            'debug' => true,
        ]);
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        ob_start();
        eval('?>'.$php);
        ob_get_clean();

        self::assertRegExp('/echo\s1;\s+\}/', $php);
    }

    /**
     * @group i
     * @covers ::<public>
     * @covers \Phug\Formatter::getDestructors
     * @covers \Phug\Formatter\AbstractFormat::getChildrenIterator
     * @covers \Phug\Formatter\AbstractFormat::formatMixinElement
     * @covers \Phug\Formatter\AbstractFormat::formatMixinCallElement
     */
    public function testScope()
    {
        $document = new DocumentElement();
        $mixin = new MixinElement();
        $mixin->setName('foo');
        $mixin->appendChild(new CodeElement('echo 1'));
        $document->appendChild($mixin);
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('foo');
        $document->appendChild($mixinCall);
        $mixin = new MixinElement();
        $mixin->setName('bar');
        $mixin->appendChild(new CodeElement('echo "A"'));
        $document->appendChild($mixin);
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('bar');
        $document->appendChild($mixinCall);

        $div = new MarkupElement('div');
        $mixin = new MixinElement();
        $mixin->setName('foo');
        $mixin->appendChild(new CodeElement('echo 2'));
        $div->appendChild($mixin);
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('foo');
        $div->appendChild($mixinCall);
        $mixin = new MixinElement();
        $mixin->setName('bar');
        $mixin->appendChild(new CodeElement('echo "B"'));
        $div->appendChild($mixin);
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('bar');
        $div->appendChild($mixinCall);
        $document->appendChild($div);

        $mixinCall = new MixinCallElement();
        $mixinCall->setName('foo');
        $document->appendChild($mixinCall);

        $mixinCall = new MixinCallElement();
        $mixinCall->setName('bar');
        $document->appendChild($mixinCall);

        $formatter = new Formatter();
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        ob_start();
        eval('?>'.$php);
        $html = ob_get_contents();
        ob_get_clean();

        self::assertSame('1A<div>2B</div>1A', $html);
    }
}
