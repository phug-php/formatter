<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\MixinCallElement;
use Phug\Formatter\Element\MixinDeclarationElement;
use Phug\Formatter\Format\HtmlFormat;
use SplObjectStorage;

/**
 * @coversDefaultClass \Phug\Formatter\AbstractFormat
 */
class MixinElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Phug\Formatter\Element\MixinCallElement::<public>
     * @covers \Phug\Formatter\Element\MixinDeclarationElement::<public>
     * @covers ::handleCode
     * @covers ::expressionsExport
     * @covers ::getExpressionValue
     * @covers ::formatMixinCallElement
     * @covers ::formatMixinDeclarationElement
     * @covers ::getExpressionValue
     * @covers ::arrayExport
     */
    public function testMixinElement()
    {
        $formatter = new Formatter();

        $link = new MixinDeclarationElement('link');
        $link->addArgument('href');
        $link->addArgument('name');
        $link->addArgument('...icons');
        $attributes = new SplObjectStorage();
        $attributes->attach(new AttributeElement('class', new ExpressionElement('$attributes->class')));
        $attributes->attach(new AttributeElement('href', new ExpressionElement('$href')));
        $attributes->attach(new AttributeElement('icons', new ExpressionElement('implode(\'-\', $icons)')));
        $aLink = new MarkupElement('a', $attributes);
        $aLink->appendChild(new ExpressionElement('$name'));
        $link->appendChild($aLink);

        $attributes = new SplObjectStorage();
        $attributes->attach(new AttributeElement('class', 'btn'));
        $attributes->attach(new AttributeElement('title', new ExpressionElement('"title"')));
        $button = new MixinCallElement('link', $attributes);
        $button->addArgument(new ExpressionElement('"/foo"'));
        $button->addArgument(new ExpressionElement('"foo"'));
        $button->addArgument(new ExpressionElement('"button"'));
        $button->addArgument(new ExpressionElement('"link"'));
        $button->addArgument(new ExpressionElement('...["foo", "bar"]'));

        $document = new DocumentElement();
        $document->appendChild($link);
        $document->appendChild($button);

        ob_start();
        eval('?>'.$formatter->format($document, HtmlFormat::class));
        $buffer = ob_get_contents();
        ob_end_clean();

        self::assertSame(
            '<a class="btn" href="/foo" icons="button-link-foo-bar">foo</a>',
            $buffer
        );
    }

    /**
     * @covers                   ::formatMixinCallElement
     * @covers                   ::formatMixinDeclarationElement
     * @expectedException        \Phug\FormatterException
     * @expectedExceptionMessage Mixin "link" not declared.
     */
    public function testBadMixinElement()
    {
        $formatter = new Formatter();
        $formatter->format(new MixinCallElement('link'), HtmlFormat::class);
    }

    /**
     * @covers                   ::formatMixinCallElement
     * @covers                   ::formatMixinDeclarationElement
     * @expectedException        \Phug\FormatterException
     * @expectedExceptionMessage Dynamic key is not allowed through mixin calls.
     */
    public function testDynamicKeyNotAllowed()
    {
        $formatter = new Formatter();
        $link = new MixinDeclarationElement('link');
        $attributes = new SplObjectStorage();
        $attributes->attach(new AttributeElement(new ExpressionElement('$href'), '/user'));
        $button = new MixinCallElement('link', $attributes);
        $document = new DocumentElement();
        $document->appendChild($link);
        $document->appendChild($button);
        $formatter->format($document, HtmlFormat::class);
    }
}
