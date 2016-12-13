<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\MarkupElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\MarkupElement
 */
class MarkupElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testMarkupElement()
    {
        $img = new MarkupElement('img', ['src' => '/foo/bar.png']);

        $this->assertSame('img', $img->getTagName());
        $this->assertSame('/foo/bar.png', $img->getAttributes()['src']);
    }
}
