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
     */
    public function testMarkupElement()
    {
        $document = new DocumentElement();

        self::assertSame('document', $document->getName());
    }
}
