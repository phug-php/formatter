<?php

namespace Phug\Test;

use Phug\AbstractFormatterModule;
use Phug\Formatter;
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Event\DependencyStorageEvent;
use Phug\Formatter\Event\FormatEvent;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Format\XmlFormat;
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
 * @coversDefaultClass Phug\AbstractFormatterModule
 */
class FormatterModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     * @covers \Phug\Formatter\Event\FormatEvent::__construct
     * @covers \Phug\Formatter\Event\FormatEvent::getElement
     * @covers \Phug\Formatter\Event\FormatEvent::setElement
     */
    public function testModule()
    {
        $formatter = new Formatter();

        $el = new MarkupElement('some-element');

        self::assertSame('<some-element></some-element>', $formatter->format($el, HtmlFormat::class));

        $formatter = new Formatter(['formatter_modules' => [TestModule::class]]);
        self::assertSame('<wrapper><renamed-element><?php $a + 1 ?></renamed-element></wrapper>', $formatter->format($el, HtmlFormat::class));
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter::__construct
     * @covers \Phug\Formatter\Event\FormatEvent::__construct
     * @covers \Phug\Formatter\Event\FormatEvent::getFormat
     * @covers \Phug\Formatter\Event\FormatEvent::setFormat
     */
    public function testFormatEvent()
    {
        $formatter = new Formatter([
            'on_format' => function (FormatEvent $e) {
                if ($e->getFormat() instanceof HtmlFormat) {
                    $e->setFormat(new XmlFormat());
                }
            },
        ]);

        $el = new MarkupElement('input');

        self::assertSame('<input></input>', $formatter->format($el, HtmlFormat::class));

        $formatter = new Formatter();

        $el = new MarkupElement('input');

        self::assertSame('<input>', $formatter->format($el, HtmlFormat::class));
    }

    /**
     * @covers ::<public>
     * @covers \Phug\Formatter::__construct
     * @covers \Phug\Formatter\Event\DependencyStorageEvent::<public>
     */
    public function testDependencyStorageEvent()
    {
        $formatter = new Formatter([
            'on_dependency_storage' => function (DependencyStorageEvent $e) {
                $e->setDependencyStorage(str_replace(
                    'foo',
                    'bar',
                    $e->getDependencyStorage()
                ));
            },
        ]);

        self::assertSame('$pugModule[\'bar\']', $formatter->getDependencyStorage('foo'));
    }
}
//@codingStandardsIgnoreEnd
