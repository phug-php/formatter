<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Element\AttributeElement
 */
class AttributeElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributes
     */
    public function testAttributeElement()
    {
        $attributes = new AttributeElement('foo', '/foo/bar.png');

        self::assertSame('foo', $attributes->getName());
        self::assertSame('/foo/bar.png', $attributes->getValue());

        $img = new MarkupElement('img');
        $attribute = new AttributeElement('src', '/foo/bar.png');
        $img->getAttributes()->attach($attribute);
        $attribute = new AttributeElement('alt', 'text');
        $img->getAttributes()->attach($attribute);
        $formatter = new Formatter([
            'default_format' => XmlFormat::class,
        ]);

        ob_start();
        $php = $formatter->format($img);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame('<img src="/foo/bar.png" alt="text"></img>', $actual);
        $attributes = new AttributeElement('foo', '/foo/bar.png');

        self::assertSame('foo', $attributes->getName());
        self::assertSame('/foo/bar.png', $attributes->getValue());
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributes
     */
    public function testExpressionAttributeElement()
    {
        $input = new MarkupElement('input');
        $attribute = new AttributeElement(new ExpressionElement('"(name)"'), 'user');
        $input->getAttributes()->attach($attribute);
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        ob_start();
        $php = $formatter->format($input);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<input (name)="user">',
            $actual
        );
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributes
     * @covers \Phug\Formatter\AbstractFormat::formatAttributeValueAccordingToName
     */
    public function testConstantAttribute()
    {
        $input = new MarkupElement('input');
        $attribute = new AttributeElement('class', new ExpressionElement("'foo'"));
        $input->getAttributes()->attach($attribute);
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        self::assertSame(
            '<input class="foo">',
            $formatter->format($input)
        );

        $input = new MarkupElement('input');
        $attribute = new AttributeElement('class', new TextElement('foo'));
        $input->getAttributes()->attach($attribute);
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        ob_start();
        $php = $formatter->format($input);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame('<input class="foo">', $actual);
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributes
     * @covers \Phug\Formatter\AbstractFormat::formatAttributeValueAccordingToName
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStandAloneAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStandAloneClassAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStandAloneStyleAttributeAssignment
     */
    public function testSpecialAttributes()
    {
        $link = new MarkupElement('a');
        $link->getAttributes()->attach(new AttributeElement('class', new ExpressionElement('[1,2,3]')));
        $link->getAttributes()->attach(new AttributeElement('data-class', new ExpressionElement('[1,2,3]')));
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        $php = $formatter->format($link);
        ob_start();
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame('<a class="1 2 3" data-class="[1,2,3]"></a>', $actual);
        $link = new MarkupElement('a');
        $link->getAttributes()->attach(new AttributeElement('class', new ExpressionElement('["a" => true, "b" => false, "c" => true]')));
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        $php = $formatter->format($link);
        ob_start();
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame('<a class="a c"></a>', $actual);

        $link = new MarkupElement('a');
        $link->getAttributes()->attach(new AttributeElement('style', new ExpressionElement('["color" => "white"]')));
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        $php = $formatter->format($link);
        ob_start();
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame('<a style="color:white"></a>', $actual);
    }
}
