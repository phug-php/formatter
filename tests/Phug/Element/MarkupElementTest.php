<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use SplObjectStorage;

/**
 * @coversDefaultClass \Phug\Formatter\Element\MarkupElement
 */
class MarkupElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Phug\Formatter\AbstractElement::__construct
     * @covers ::__construct
     * @covers ::getAttribute
     */
    public function testMarkupElement()
    {
        $attributes = new SplObjectStorage();
        $source = new AttributeElement('src', '/foo/bar.png');
        $attributes->attach($source);
        $img = new MarkupElement('img', $attributes);
        $altValue = new CodeElement('$alt');
        $alt = new AttributeElement('alt', $altValue);
        $img->getAttributes()->attach($alt);
        $mysteryCode = new CodeElement('$mystery');
        $mystery = new AttributeElement($mysteryCode, '42');
        $img->getAttributes()->attach($mystery);

        self::assertSame('img', $img->getName());
        self::assertTrue($img->getAttributes()->contains($source));
        self::assertTrue($img->getAttributes()->contains($alt));
        self::assertTrue($img->getAttributes()->contains($mystery));
        self::assertSame('/foo/bar.png', $img->getAttribute('src'));
        self::assertSame($altValue, $img->getAttribute('alt'));
        self::assertSame('42', $img->getAttribute($mysteryCode));
        self::assertSame(null, $img->getAttribute('foo'));
    }

    /**
     * @covers ::belongsTo
     */
    public function testBelongsTo()
    {
        $img = new MarkupElement('img');

        self::assertTrue($img->belongsTo(['input', 'img']));
        self::assertFalse($img->belongsTo(['input', 'link']));

        $img = new MarkupElement(new ExpressionElement('"link"'));

        self::assertFalse($img->belongsTo(['input', 'link']));
    }
}
