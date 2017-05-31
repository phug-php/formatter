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

        self::assertSame(
            '<img src="/foo/bar.png" alt="text"></img>',
            $formatter->format($img)
        );
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

        self::assertSame(
            '<input (name)="user">',
            $formatter->format($input)
        );
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributes
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

        self::assertSame(
            '<input class="foo">',
            $formatter->format($input)
        );
    }
}
