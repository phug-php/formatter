<?php

namespace Phug\Test\Format;

use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Format\XmlFormat
 */
class XmlFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__invoke
     * @covers \Phug\Formatter\AbstractFormat::formatDoctypeElement
     */
    public function testXmlFormat()
    {

    	$document = new DocumentElement();
    	$document->appendChild(new DoctypeElement());
    	$document->appendChild(new MarkupElement('img'));
        $xmlFormat = new XmlFormat();

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?><img />',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::isSelfClosingTag
     * @covers ::isBlockTag
     * @covers ::formatMarkupElement
     * @covers ::formatTagChildren
     * @covers ::formatPairTag
     */
    public function testCustomFormatHandler()
    {

        $img = new MarkupElement('img');
        $xmlFormat = new XmlFormat();
        $xmlFormat->setElementHandler(MarkupElement::class, function (ElementInterface $element) {
            return strtoupper($element->getName());
        });

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?>IMG',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testMissingFormatHandler()
    {

        $img = new MarkupElement('img');
        $xmlFormat = new XmlFormat();
        $xmlFormat->removeElementHandler(MarkupElement::class);

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?>',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::formatMarkupElement
     * @covers ::formatAttributeElement
     * @covers ::formatTagChildren
     * @covers ::formatPairTag
     */
    public function testFormatSingleTagWithAttributes()
    {

        $img = new MarkupElement('img');
        $img->getAttributes()->attach(new AttributeElement('src', 'foo.png'));
        $xmlFormat = new XmlFormat();

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?><img src="foo.png" />',
            $xmlFormat($img)
        );
    }

    /**
     * @covers ::formatMarkupElement
     * @covers ::formatAttributeElement
     * @covers ::formatCodeElement
     * @covers ::formatTagChildren
     * @covers ::formatPairTag
     */
    public function testFormatBooleanTrueAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new CodeElement('true')));
        $xmlFormat = new XmlFormat();

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?><input type="checkbox" checked="checked" />',
            $xmlFormat($input)
        );
    }

    /**
     * @covers ::formatMarkupElement
     * @covers ::formatAttributeElement
     * @covers ::formatTagChildren
     * @covers ::formatPairTag
     */
    public function testFormatBooleanFalseAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new CodeElement('false')));
        $xmlFormat = new XmlFormat();

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?><input type="checkbox" />',
            $xmlFormat($input)
        );
    }

    /**
     * @covers ::isSelfClosingTag
     * @covers ::isBlockTag
     * @covers ::formatTagChildren
     * @covers ::formatPairTag
     * @covers ::formatMarkupElement
     */
    public function testChildrenInATag()
    {

        $input = new MarkupElement('input');
        $input->appendChild(new MarkupElement('i'));
        $xmlFormat = new XmlFormat();

        self::assertSame(
            '<?xml version="1.0" encoding="utf-8" ?><input><i /></input>',
            $xmlFormat($input)
        );
    }
}
