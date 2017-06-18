<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\AbstractValueElement
 */
class ExpressionElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testExpressionElement()
    {
        $expression = new ExpressionElement('0');
        $formatter = new Formatter([
            'default_format' => XmlFormat::class,
        ]);

        self::assertSame(
            '0',
            $formatter->format($expression)
        );
    }
}
