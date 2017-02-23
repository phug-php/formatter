<?php

namespace Phug\Test\Partial;

use Phug\Formatter;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Partial\AssignmentHelpersTrait
 */
class AssignmentHelpersTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group i
     * @covers ::provideAttributeAssignments
     */
    public function testAttributeAssignments()
    {
        $format = new XmlFormat();
        $attributes = ['class' => 'foo', 'foobar' => '11'];
        $helper = $format->getHelper('attribute_assignments');

        self::assertSame(
            'foo bar',
            $helper($attributes, 'class', 'bar')
        );

        self::assertSame(
            '22',
            $helper($attributes, 'foobar', '22')
        );
    }

    /**
     * @covers ::provideAttributeAssignment
     */
    public function testAttributeAssignment()
    {
        # code...
    }

    /**
     * @covers ::provideAttributesAssignment
     */
    public function testAttributesAssignment()
    {
        # code...
    }

    /**
     * @covers ::provideClassAttributeAssignment
     */
    public function testClassAttributeAssignment()
    {
        # code...
    }

    /**
     * @covers ::provideStyleAttributeAssignment
     */
    public function testStyleAttributeAssignment()
    {
        # code...
    }
}
