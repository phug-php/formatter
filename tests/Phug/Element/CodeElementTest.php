<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\CodeElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\CodeElement
 */
class CodeElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testCodeElement()
    {
        $foo = new CodeElement('echo $foo;');

        self::assertSame('echo $foo;', $foo->getValue());
    }
}
