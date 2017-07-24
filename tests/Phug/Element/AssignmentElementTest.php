<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\TextElement;
use Phug\Formatter\Format\HtmlFormat;
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
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStandAloneAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideAttributesAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideClassAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStandAloneClassAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStyleAttributeAssignment
     * @covers \Phug\Formatter\Partial\AssignmentHelpersTrait::provideStandAloneStyleAttributeAssignment
     * @covers \Phug\Formatter\AbstractFormat::formatPairAsArrayItem
     * @covers \Phug\Formatter\AbstractFormat::attributesAssignmentsFromPairs
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
        $formatter = new Formatter();

        self::assertSame(
            '',
            $formatter->formatDependencies()
        );

        ob_start();
        $php = $formatter->format($img);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<img alt="Foo" src="/foo/bar.png" />',
            $actual
        );

        $img = new MarkupElement('img');
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement(
            '['.
                '"class" => ["baz", "foo", "foobar"],'.
                '"style" => ["width" => "200px", "display" => "block"]'.
            ']'
        ));
        $assignment = new AssignmentElement('attributes', $data, $img);
        $img->getAssignments()->attach($assignment);
        $img->getAttributes()->attach(new AttributeElement('class', 'foo bar'));
        $img->getAttributes()->attach(new AttributeElement('style', 'height: 100px; z-index: 9;'));

        ob_start();
        $php = $formatter->format($img);
        eval('?>'.$formatter->formatDependencies().$php);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<img class="baz foo foobar bar" style="width:200px;display:block;height: 100px; z-index: 9;" />',
            $actual
        );

        $input = new MarkupElement('input');
        $attribute = new AttributeElement('class', new TextElement('foo'));
        $input->getAttributes()->attach($attribute);
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["class" => "top bottom"]'));
        $assignment = new AssignmentElement('attributes', $data);
        $input->addAssignment($assignment);
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);
        $phtml = $formatter->format($input);
        ob_start();
        eval('?>'.$formatter->formatDependencies().$phtml);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<input class="top bottom foo">',
            $actual
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
            'default_format' => XmlFormat::class,
        ]);
        $formatter->format($img);
    }

    /**
     * @covers \Phug\Formatter\Format\XmlFormat::formatAssignmentElement
     * @covers \Phug\Formatter\Element\ExpressionElement::<public>
     * @covers \Phug\Formatter\Partial\TransformableTrait::preventFromTransformation
     * @covers \Phug\Formatter\Partial\TransformableTrait::isTransformationAllowed
     */
    public function testAssignmentHandlersOption()
    {
        $img = new MarkupElement('img', true);
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('["user" => "Bob"]'));
        $assignment = new AssignmentElement('data', $data, $img);
        $img->getAssignments()->attach($assignment);
        $formatter = new Formatter([
            'default_format'      => XmlFormat::class,
            'assignment_handlers' => [
                function (AssignmentElement $element) {
                    $markup = $element->getMarkup();
                    foreach ($markup->getAssignmentsByName('data') as $dataAssignment) {
                        $attributesAssignment = new AssignmentElement('attributes', null, $markup);
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
        $phtml = $formatter->format($img);
        ob_start();
        eval('?>'.$formatter->formatDependencies().$phtml);
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<img data-user="Bob" data-foo="bar" bar="foo" />',
            $actual
        );
    }

    /**
     * @covers \Phug\Formatter\Element\AssignmentElement::detach
     * @covers \Phug\Formatter\Format\XmlFormat::formatAssignmentElement
     */
    public function testAssignmentHandlersWithYield()
    {
        $img = new MarkupElement('img', true);
        $data = new SplObjectStorage();
        $data->attach(new ExpressionElement('$var'));
        $assignment = new AssignmentElement('foo', $data, $img);
        $img->getAssignments()->attach($assignment);
        $formatter = new Formatter([
            'default_format'      => XmlFormat::class,
            'assignment_handlers' => [
                function (AssignmentElement $element) {
                    if ($element->getName() === 'foo') {
                        $element->detach();
                        yield new ExpressionElement(
                            'my_func('.implode(', ', array_map(function (ExpressionElement $attribute) {
                                return $attribute->getValue();
                            }, iterator_to_array($element->getAttributes()))).')'
                        );
                    }
                },
            ],
        ]);

        self::assertSame(
            '<img<?= (is_bool($_pug_temp = '.
            'my_func((isset($var) ? $var : \'\'))'.
            ') ? var_export($_pug_temp, true) : $_pug_temp) ?> />',
            $formatter->format($img)
        );
    }

    /**
     * @covers \Phug\Formatter::formatAttributesList
     * @covers \Phug\Formatter\Format\AbstractFormat::formatAttributesList
     * @covers \Phug\Formatter\Format\AbstractFormat::arrayToPairsExports
     * @covers \Phug\Formatter\Format\AbstractFormat::formatPairAsArrayItem
     */
    public function testFormatAttributesList()
    {
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);
        $list = $formatter->formatAttributesList([
            new AttributeElement('name', new ExpressionElement('["class" => "top bottom"]')),
        ]);

        self::assertInstanceOf(ExpressionElement::class, $list);
        self::assertSame('$pugModule['.
            '\'Phug\\\\Formatter\\\\Format\\\\HtmlFormat::merge_attributes\']'.
            '([\'name\' => ["class" => "top bottom"]])', $list->getValue());
    }
}
