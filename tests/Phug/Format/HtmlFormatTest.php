<?php

namespace Phug\Test\Format;

use Phug\Formatter\ElementInterface;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\HtmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Format\HtmlFormat
 */
class HtmlFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testHtmlFormat()
    {

        $img = new MarkupElement('img');
        $htmlFormat = new HtmlFormat();

        self::assertSame(
            '<!DOCTYPE html><img>',
            $htmlFormat($img)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testCustomFormatHandler()
    {

        $img = new MarkupElement('img');
        $htmlFormat = new HtmlFormat();
        $htmlFormat->setElementHandler(MarkupElement::class, function (ElementInterface $element) {
            return strtoupper($element->getName());
        });

        self::assertSame(
            '<!DOCTYPE html>IMG',
            $htmlFormat($img)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testMissingFormatHandler()
    {

        $img = new MarkupElement('img');
        $htmlFormat = new HtmlFormat();
        $htmlFormat->removeElementHandler(MarkupElement::class);

        self::assertSame(
            '<!DOCTYPE html>',
            $htmlFormat($img)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testFormatSingleTagWithAttributes()
    {

        $img = new MarkupElement('img');
        $img->getAttributes()->attach(new AttributeElement('src', 'foo.png'));
        $htmlFormat = new HtmlFormat();

        self::assertSame(
            '<!DOCTYPE html><img src="foo.png">',
            $htmlFormat($img)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testFormatBooleanTrueAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new CodeElement('true')));
        $htmlFormat = new HtmlFormat();

        self::assertSame(
            '<!DOCTYPE html><input type="checkbox" checked>',
            $htmlFormat($input)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testFormatBooleanNullAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'checkbox'));
        $input->getAttributes()->attach(new AttributeElement('checked', new CodeElement('null')));
        $htmlFormat = new HtmlFormat();

        self::assertSame(
            '<!DOCTYPE html><input type="checkbox">',
            $htmlFormat($input)
        );
    }

    /**
     * @covers ::<public>
     */
    public function testFormatCodeAttribute()
    {

        $input = new MarkupElement('input');
        $input->getAttributes()->attach(new AttributeElement('type', 'text'));
        $input->getAttributes()->attach(new AttributeElement('value', new CodeElement('a_function(42)')));
        $htmlFormat = new HtmlFormat();

        self::assertSame(
            '<!DOCTYPE html><input type="text" value="<?php echo a_function(42); ?>">',
            $htmlFormat($input)
        );
    }

    /**
     * @covers                   ::isSelfClosingTag
     * @expectedException        \Phug\FormatterException
     * @expectedExceptionMessage input is a self closing element: <input/> but contains nested content.
     */
    public function testChildrenInSelfClosingTag()
    {

        $input = new MarkupElement('input');
        $input->appendChild(new MarkupElement('i'));
        $htmlFormat = new HtmlFormat();
        $htmlFormat($input);
    }

    /**
     * @covers ::isBlockTag
     * @covers \Phug\Formatter\AbstractFormat::formatDoctypeElement
     */
    public function testCustomDoctype()
    {

    	$document = new DocumentElement();
    	$document->appendChild(new DoctypeElement('html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN"'));
        $document->appendChild(new MarkupElement('html'));
        $htmlFormat = new HtmlFormat();
        self::assertSame(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN"><html></html>',
            $htmlFormat($input)
        );
    }
}
