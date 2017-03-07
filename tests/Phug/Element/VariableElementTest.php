<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\VariableElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\VariableElement
 */
class VariableElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Phug\Formatter\AbstractFormat::formatVariableElement
     * @covers ::<public>
     */
    public function testVariableElement()
    {
        $variable = new VariableElement(
            new CodeElement('$foo'),
            new ExpressionElement('42')
        );
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        self::assertSame('<?php $foo=42 ?>', $formatter->format($variable));

        $value = new ExpressionElement('$bar');
        $value->escape();
        $value->check();
        $variable = new VariableElement(
            new CodeElement('$foo'),
            $value
        );
        $formatter = new Formatter([
            'default_format' => HtmlFormat::class,
        ]);

        self::assertSame('<?php $foo=htmlspecialchars((isset($bar) ? $bar : \'\')) ?>', $formatter->format($variable));
    }
}
