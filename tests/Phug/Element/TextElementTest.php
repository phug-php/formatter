<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\TextElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\TextElement
 */
class TextElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testTextElement()
    {
        $text = new TextElement('foobar');

        self::assertSame(null, $text->isEnd());
        $text->setEnd(true);
        self::assertTrue($text->isEnd());
        $text->setEnd(false);
        self::assertFalse($text->isEnd());
    }
}
