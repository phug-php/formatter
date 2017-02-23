<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\XmlFormat;
use SplObjectStorage;

/**
 * @coversDefaultClass \Phug\Formatter\Element\AssignmentElement
 */
class AssignmentElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideAttributeAssignments
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideAttributesAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideClassAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStyleAttributeAssignment
     * @covers \Phug\Formatter\Format\XmlFormat::addAttributeAssignment
     * @covers \Phug\Formatter\Format\XmlFormat::requireHelper
     * @covers \Phug\Formatter\Format\XmlFormat::formatMarkupElement
     * @covers \Phug\Formatter\Format\XmlFormat::formatAssignmentValue
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributeAsArrayItem
     * @covers \Phug\Formatter\Format\XmlFormat::formatAssignmentElement
     * @covers \Phug\Formatter\Format\XmlFormat::formatAttributes
     */
    public function testAttributeElement()
    {
        $img = new MarkupElement('img');
        $attributes = new AttributeElement('src', '/foo/bar.png');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["alt" => "Foo"]'));
        $assignment = new AssignmentElement('attributes', $data, $img);
        $img->getAssignments()->attach($assignment);
        $img->getAttributes()->attach($attributes);
        $formatter = new Formatter([
            'default_class_name' => XmlFormat::class,
        ]);

        self::assertSame(
            '',
            $formatter->formatDependencies()
        );

        self::assertSame(
            '<img<?= $pugModule['.
            '\'Phug\\\\Formatter\\\\Format\\\\BasicFormat::attributes_assignment\']'.
            '(["alt" => "Foo"], [\'src\' => \'/foo/bar.png\']) ?> />',
            $formatter->format($img)
        );

        $attributes = eval('?>'.$formatter->formatDependencies().'<?php return $pugModule['.
            '\'Phug\\\\Formatter\\\\Format\\\\BasicFormat::attributes_assignment\']'.
            '(["alt" => "Foo"], [\'src\' => \'/foo/bar.png\']);');

        self::assertSame(
            ' alt="Foo" src="/foo/bar.png"',
            $attributes
        );

        $img = new MarkupElement('img');
        $attributes = new AttributeElement('src', '/foo/bar.png');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["alt" => "Foo"]'));
        $assignment = new AssignmentElement('attributes', $data, $img);
        $img->getAssignments()->attach($assignment);
        $img->getAttributes()->attach($attributes);
        $formatter->initDependencies()->format($img);

        $attributes = eval('?>'.$formatter->formatDependencies().'<?php return $pugModule['.
            '\'Phug\\\\Formatter\\\\Format\\\\BasicFormat::attributes_assignment\']'.
            '(["class" => "foo bar", "style" => "height: 100px; z-index: 9;"], '.
            '[\'style\' => '.
            '[\'width\' => \'200px\', \'display\' => \'block\'],'.
            '\'class\' => [\'baz\', \'foo\', \'foobar\']]'.
            ');');

        self::assertSame(
            ' class="foo bar baz foobar" style="height: 100px; z-index: 9;width:200px;display:block"',
            $attributes
        );
    }

    /**
     * @covers                   \Phug\Formatter\Format\XmlFormat::formatAssignmentElement
     * @expectedException        \Phug\FormatterException
     * @expectedExceptionMessage Unable to handle class assignment
     */
    public function testFormatAssignmentElementException()
    {
        $img = new MarkupElement('img');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('[1]'));
        $assignment = new AssignmentElement('class', $data, $img);
        $img->getAssignments()->attach($assignment);
        $formatter = new Formatter([
            'default_class_name' => XmlFormat::class,
        ]);
        $formatter->format($img);
    }

    /**
     * @group i
     * @covers \Phug\Formatter\Format\XmlFormat::formatAssignmentElement
     */
    public function testAssignmentHandlersOption()
    {
        $img = new MarkupElement('img');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["user" => "Bob"]'));
        $assignment = new AssignmentElement('data', $data, $img);
        $img->getAssignments()->attach($assignment);
        $formatter = new Formatter([
            'default_class_name'  => XmlFormat::class,
            'assignment_handlers' => [
                function (AssignmentElement $element) {
                    $markup = $element->getMarkup();
                    foreach ($markup->getAssignmentsByName('data') as $dataAssignment) {
                        $attributesAssignment = new AssignmentElement('attributes', $markup);
                        /**
                         * @var AssignmentElement $dataAssignment
                         */
                        foreach ($dataAssignment->getAttributes() as $attribute) {
                            $expression = new ExpressionElement(
                                'call_user_func(function ($data) { '.
                                    '$result = []; '.
                                    'foreach ($data as $name => $value) { '.
                                        '$result["data-".$name] = $value; '.
                                    '} '.
                                    'return $result; '.
                                '}, '.$attribute->getValue().')'
                            );
                            $expression->uncheck();
                            $attributesAssignment->getAttributes()->attach($expression);
                        }
                        $markup->removedAssignment($dataAssignment);
                        $markup->addAssignment($attributesAssignment);
                    }

                    return [];
                },
            ],
        ]);
        $img->getAttributes()->attach(new AttributeElement('data-foo', 'bar'));
        $img->getAttributes()->attach(new AttributeElement('bar', 'foo'));

        self::assertSame(
            '<img<?= '.
            '$pugModule[\'Phug\\\\Formatter\\\\Format\\\\BasicFormat::attributes_assignment\']'.
            '(call_user_func(function ($data) { $result = []; foreach ($data as $name => $value) '.
            '{ $result["data-".$name] = $value; } '.
            'return $result; }, ["user" => "Bob"]), '.
            '[\'data-foo\' => \'bar\', \'bar\' => \'foo\']) ?> />',
            $formatter->format($img)
        );

        self::assertSame(
            ' data-user="Bob" data-foo="bar" bar="foo"',
            eval(
                '?>'.$formatter->formatDependencies().'<?php '.
                'return $pugModule[\'Phug\\\\Formatter\\\\Format\\\\BasicFormat::attributes_assignment\']'.
                '(call_user_func(function ($data) { $result = []; foreach ($data as $name => $value) '.
                '{ $result["data-".$name] = $value; } '.
                'return $result; }, ["user" => "Bob"]), '.
                '[\'data-foo\' => \'bar\', \'bar\' => \'foo\']);'
            )
        );
    }
}
