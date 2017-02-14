<?php

namespace Phug\Test\Format;

use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Format\XmlFormat
 */
class XmlFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers \Phug\Formatter\AbstractFormat::__construct
     * @covers ::__invoke
     * @covers \Phug\Formatter\AbstractFormat::formatDoctypeElement
     */
    public function testXmlFormat()
    {
        $document = new DocumentElement();
        $document->appendChild(new DoctypeElement());
        $document->appendChild(new MarkupElement('img'));
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?><img />',
            $xmlFormat($document)
        );
    }

    /**
     * @covers ::isSelfClosingTag
     * @covers ::isBlockTag
     * @covers ::formatMarkupElement
     * @covers ::formatElementChildren
     * @covers ::formatPairTag
     */
    public function testCustomFormatHandler()
    {
        $img = new MarkupElement('img');
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));
        $xmlFormat->setElementHandler(MarkupElement::class, function (ElementInterface $element) {
            return strtoupper($element->getName());
        });

        self::assertSame(
            'IMG',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testMissingFormatHandler()
    {
        $img = new MarkupElement('img');
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));
        $xmlFormat->removeElementHandler(MarkupElement::class);

        self::assertSame(
            '',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::formatMarkupElement
     * @covers ::formatAttributeElement
     * @covers ::formatElementChildren
     * @covers ::formatPairTag
     */
    public function testFormatSingleTagWithAttributes()
    {
        $img = new MarkupElement('img');
        $img->getAttributes()->attach(new AttributeElement('src', 'foo.png'));
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));

        self::assertSame(
            '<img src="foo.png" />',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::formatMarkupElement
     * @covers ::formatAttributeElement
     * @covers ::formatExpressionElement
     * @covers ::formatElementChildren
     * @covers ::formatPairTag
     */
    public function testFormatBooleanTrueAttribute()
    {
        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new ExpressionElement('true')));
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));

        self::assertSame(
            '<input type="checkbox" checked="checked" />',
            $xmlFormat($input)
        );

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement(new ExpressionElement('$foo'), 'checked'));

        self::assertSame(
            '<input type="checkbox" <?= (isset($foo) ? $foo : \'\') ?>="checked" />',
            $xmlFormat($input)
        );

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement(new ExpressionElement('"(name)"'), new ExpressionElement('"user"')));

        self::assertSame(
            '<input (name)="user" />',
            $xmlFormat($input)
        );

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement(
            new ExpressionElement('$foo'),
            new ExpressionElement('$bar')
        ));

        self::assertSame(
            '<input type="checkbox" <?= (isset($foo) ? $foo : \'\') ?>="<?= (isset($bar) ? $bar : \'\') ?>" />',
            $xmlFormat($input)
        );

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement(
            new ExpressionElement('$foo'),
            new ExpressionElement('true')
        ));

        self::assertSame(
            '<input type="checkbox" '.
            '<?= $__value=(isset($foo) ? $foo : \'\') ?>='.
            '"<?= (isset($__value) ? $__value : \'\') ?>" />',
            $xmlFormat($input)
        );
    }

    /**
     * @covers ::formatMarkupElement
     * @covers ::formatAttributeElement
     * @covers ::formatElementChildren
     * @covers ::formatPairTag
     */
    public function testFormatBooleanFalseAttribute()
    {
        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new ExpressionElement('false')));
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));

        self::assertSame(
            '<input type="checkbox" />',
            $xmlFormat($input)
        );
    }

    /**
     * @covers ::isSelfClosingTag
     * @covers ::isBlockTag
     * @covers ::formatElementChildren
     * @covers ::formatPairTag
     * @covers ::formatMarkupElement
     */
    public function testChildrenInATag()
    {
        $input = new MarkupElement('input');
        $input->appendChild(new MarkupElement('i'));
        $xmlFormat = new XmlFormat(new Formatter([
            'default_format' => XmlFormat::class,
        ]));

        self::assertSame(
            '<input><i /></input>',
            $xmlFormat($input)
        );
    }
}
