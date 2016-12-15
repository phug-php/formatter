<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\AttributeElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\AttributeElement
 */
class AttributeElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testAttributeElement()
    {

        $attributes = new AttributeElement('foo', '/foo/bar.png');

        $this->assertSame('foo', $attributes->getKey());
        $this->assertSame('/foo/bar.png', $attributes->getItem());
    }
}
