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
     * @covers \Phug\Formatter\Format\XmlFormat::formatMarkupElement
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
    }
}
