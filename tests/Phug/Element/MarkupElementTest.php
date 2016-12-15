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

        $this->assertSame('img', $img->getName());
        $this->assertTrue($img->getAttributes()->contains($source));
        $this->assertTrue($img->getAttributes()->contains($alt));
        $this->assertTrue($img->getAttributes()->contains($mystery));
        $this->assertSame('/foo/bar.png', $img->getAttribute('src'));
        $this->assertSame($altValue, $img->getAttribute('alt'));
        $this->assertSame('42', $img->getAttribute($mysteryCode));
    }
}
