<?php

namespace Phug\Test;

use Phug\AbstractFormatterModule;
use Phug\Formatter;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Event\FormatEvent;
use Phug\Formatter\Format\HtmlFormat;
use Phug\FormatterEvent;

//@codingStandardsIgnoreStart
class TestModule extends AbstractFormatterModule
{
    public function getEventListeners()
    {
        return [
            FormatterEvent::FORMAT => function (FormatEvent $e) {
                $el = $e->getElement();
                if ($el instanceof MarkupElement && $el->getName() === 'some-element') {
                    $wrapper = new MarkupElement('wrapper');
                    $wrapper->appendChild($el);

                    $el->setName('renamed-element'); //Notice that we'd create an endless loop if we wouldn't rename it
                    $el->appendChild(new CodeElement('$a + 1'));

                    $e->setElement($wrapper);
                }
            },
        ];
    }
}

/**
 * @coversDefaultClass Phug\FormatterModule
 */
class FormatterModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testModule()
    {
        $formatter = new Formatter();

        $el = new MarkupElement('some-element');

        self::assertSame('<some-element></some-element>', $formatter->format($el, HtmlFormat::class));

        $formatter = new Formatter(['modules' => [TestModule::class]]);
        self::assertSame('<wrapper><renamed-element><?php $a + 1 ?></renamed-element></wrapper>', $formatter->format($el, HtmlFormat::class));
    }
}
//@codingStandardsIgnoreEnd
