<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\DocumentElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\DocumentElement
 */
class DocumentElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getName
     * @covers ::isAutoClosed
     */
    public function testMarkupElement()
    {
        $document = new DocumentElement();

        self::assertFalse($document->isAutoClosed());
        self::assertSame('document', $document->getName());
    }
}
