<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\XmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Element\AbstractValueElement
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

        $paragraph = new MarkupElement('p');
        $paragraph->getAttributes()->attach(
            $attrEl = new AttributeElement('foo', new ExpressionElement('true'))
        );
        $paragraph->appendChild(new ExpressionElement('true'));
        ob_start();
        eval('?>'.$formatter->format($paragraph));
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<p foo="foo">true</p>',
            $actual
        );

        $paragraph = new MarkupElement('p');
        $paragraph->getAttributes()->attach(
            new AttributeElement('foo', new ExpressionElement('false'))
        );
        $paragraph->appendChild(new ExpressionElement('false'));
        ob_start();
        eval('?>'.$formatter->format($paragraph));
        $actual = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<p>false</p>',
            $actual
        );
    }
}
