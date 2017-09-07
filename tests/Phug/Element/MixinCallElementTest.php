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
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
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
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
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
     * @covers ::<public>
     */
    public function testUnknownMixinDebugOn()
    {
        $document = new DocumentElement();
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('undef');
        $document->appendChild($mixinCall);

        $formatter = new Formatter(array(
            'debug' => true,
        ));
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
     */
    public function testUnknownMixinDebugOff()
    {
        $document = new DocumentElement();
        $mixinCall = new MixinCallElement();
        $mixinCall->setName('undef');
        $document->appendChild($mixinCall);
        $document->appendChild(new TextElement('next'));

        $formatter = new Formatter(array(
            'debug' => false,
        ));
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
}
