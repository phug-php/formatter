<?php

namespace Phug\Test\Format;

use Phug\Formatter;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\Format\BasicFormat;
use Phug\Formatter\Format\XmlFormat;
use SplObjectStorage;

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
        $input->getAttributes()->attach(
            new AttributeElement(
                new ExpressionElement('"(name)"'),
                new ExpressionElement('"user"')
            )
        );

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

    /**
     * @covers \Phug\Formatter\AbstractFormat::helperName
     * @covers \Phug\Formatter\AbstractFormat::requireHelper
     */
    public function testHelperName()
    {
        $formatter = new Formatter([
            'default_format' => XmlFormat::class,
        ]);
        $xmlFormat = new XmlFormat($formatter);
        $xmlFormat->provideHelper('foo', function () {
            return function () {
                return 1;
            };
        });

        $states = $formatter->getDependencies()->getRequirementsStates();

        self::assertTrue(isset($states[XmlFormat::class.'::foo']));
        self::assertFalse($states[XmlFormat::class.'::foo']);

        $xmlFormat->requireHelper('foo');
        $states = $formatter->getDependencies()->getRequirementsStates();

        self::assertTrue($states[XmlFormat::class.'::foo']);
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::patternName
     * @covers \Phug\Formatter\AbstractFormat::addPattern
     * @covers \Phug\Formatter\AbstractFormat::exportHelper
     */
    public function testAddPattern()
    {
        $formatter = new Formatter([
            'default_format' => BasicFormat::class,
        ]);
        $xmlFormat = new BasicFormat($formatter);
        $xmlFormat->addPattern('foo', [function () {
            return function () {
                return 1;
            };
        }]);

        $states = $formatter->getDependencies()->getRequirementsStates();

        self::assertTrue(isset($states[BasicFormat::class.'::pattern.foo']));
        self::assertFalse($states[BasicFormat::class.'::pattern.foo']);
        self::assertSame(
            '$pugModule[\''.addslashes(BasicFormat::class).'::pattern.foo\']',
            $xmlFormat->exportHelper('pattern.foo')
        );

        $states = $formatter->getDependencies()->getRequirementsStates();

        self::assertTrue($states[BasicFormat::class.'::pattern.foo']);
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::setFormatter
     */
    public function testGetDynamicHelper()
    {
        $formatter = new Formatter([
            'default_format' => BasicFormat::class,
        ]);
        $basicFormat = new BasicFormat($formatter);
        $basicFormat->provideHelper('foobar', [function () {
            return function () {
                return 123;
            };
        }]);
        $basicFormat->provideHelper('test', ['get_helper', function ($getHelper) {
            return function ($name) use ($getHelper) {
                return call_user_func($getHelper($name)) + 5;
            };
        }]);
        $function = $basicFormat->exportHelper('foobar');
        $actual = eval('?>'.$formatter->formatDependencies().'<?php return '.$function.'();');

        self::assertSame(123, $actual);

        $function = $basicFormat->exportHelper('test');
        $actual = eval('?>'.$formatter->formatDependencies().'<?php return '.$function.'("foobar");');

        self::assertSame(128, $actual);
    }

    /**
     * @covers \Phug\Formatter\AbstractFormat::__construct
     */
    public function testNativePhpHelperCall()
    {
        $formatter = new Formatter([
            'default_format' => BasicFormat::class,
        ]);
        $basicFormat = new BasicFormat($formatter);
        $function = $basicFormat->exportHelper('pattern.html_text_escape');
        $actual = eval('?>'.$formatter->formatDependencies().'<?php return '.$function.'("<>");');

        self::assertSame('&lt;&gt;', $actual);

        $text = new TextElement('<>');
        $text->escape();

        self::assertSame('&lt;&gt;', $formatter->format($text));
    }

    /**
     * @group i
     * @covers ::__construct
     * @covers ::formatAssignmentValue
     * @covers ::formatAssignmentElement
     * @covers ::formatAttributes
     */
    public function testAttributeAssignmentsOption()
    {
        $formatter = new Formatter([
            'default_format'        => XmlFormat::class,
            'attribute_assignments' => [
                'data-user' => function (&$attributes, $value) {
                    $data = isset($attributes['data-user']) ? json_decode($attributes['data-user']) : [];
                    $value = is_string($value) ? json_decode($value) : $value;

                    return json_encode(array_merge_recursive((array) $data, (array) $value));
                },
            ],
        ]);
        $link = new MarkupElement('a');
        $data = new SplObjectStorage();
        $attributes = new AttributeElement('data-user', new ExpressionElement('"{\"name\":{\"first\":\"Linus\"}}"'));
        $link->getAttributes()->attach($attributes);
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["data-user" => ["name" => ["last" => "Trosvald"]]]'));
        $link->addAssignment(new AssignmentElement('attributes', $data, $link));

        self::assertSame(
            '<a<?= $pugModule[\'Phug\\\\Formatter\\\\Format\\\\XmlFormat::attributes_assignment\']'.
            '(["data-user" => ["name" => ["last" => "Trosvald"]]], '.
            '[\'data-user\' => "{\"name\":{\"first\":\"Linus\"}}"]) ?> />',
            $formatter->format($link)
        );

        $attributes = eval('?>'.$formatter->formatDependencies().'<?php return '.
            '$pugModule[\'Phug\\\\Formatter\\\\Format\\\\XmlFormat::attributes_assignment\']'.
            '(["data-user" => ["name" => ["last" => "Trosvald"]]], '.
            '[\'data-user\' => "{\"name\":{\"first\":\"Linus\"}}"]);');

        self::assertSame(
            ' data-user="{"name":{"last":"Trosvald","first":"Linus"}}"',
            $attributes
        );
    }
}
