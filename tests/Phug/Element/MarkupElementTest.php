<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
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

        $img = new MarkupElement('img');
        $source = new AttributeElement('src', '/foo/bar.png');
        $img->getAttributes()->attach($source);
        $altValue = new CodeElement('echo $alt;');
        $alt = new AttributeElement('alt', $altValue);
        $img->getAttributes()->attach($alt);
        $mysteryCode = new CodeElement('echo $mystery;');
        $mystery = new AttributeElement($mysteryCode, '42');
        $img->getAttributes()->attach($mystery);

        self::assertSame('img', $img->getName());
        self::assertTrue($img->getAttributes()->contains($source));
        self::assertTrue($img->getAttributes()->contains($alt));
        self::assertTrue($img->getAttributes()->contains($mystery));
        self::assertSame('/foo/bar.png', $img->getAttribute('src'));
        self::assertSame($altValue, $img->getAttribute('alt'));
        self::assertSame('42', $img->getAttribute($mysteryCode));
    }
}
