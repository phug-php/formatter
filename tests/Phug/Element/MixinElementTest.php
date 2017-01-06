<?php

namespace Phug\Test\Element;

use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\MixinDeclarationElement;
use Phug\Formatter\Element\MixinCallElement;
use Phug\Formatter;
use Phug\Formatter\Format\HtmlFormat;
use SplObjectStorage;

/**
 * @coversDefaultClass \Phug\Formatter\Element\MixinDeclarationElement
 */
class MixinElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     * @expectedException        \Phug\FormatterException
     * @expectedExceptionMessage Mixin nolink not declared.
     */
    public function testMixinElement()
    {
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

        $formatter = new Formatter();

        $attributes = new SplObjectStorage();
        $attributes->attach(new AttributeElement('class', 'btn'));
        $button = new MixinCallElement('nolink', $attributes);
        $button->addArgument('/foo');
        $button->addArgument('foo');
        $button->addArgument('button');
        $button->addArgument('link');

        self::assertSame(
            '<a class="btn" href="/foo" icons="button-link">foo</a>',
            $formatter->format($button, HtmlFormat::class)
        );
    }
}
