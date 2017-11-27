<?php

namespace Phug\Test\Element;

use PHPUnit\Framework\TestCase;
use Phug\Formatter\Element\CodeElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\CodeElement
 */
class CodeElementTest extends TestCase
{
    /**
     * @covers ::<public>
     */
    public function testCodeElement()
    {
        $foo = new CodeElement('$foo');

        self::assertSame('$foo', $foo->getValue());
    }
}
