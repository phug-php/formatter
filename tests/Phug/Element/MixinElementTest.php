<?php

namespace Phug\Test\Element;

use Phug\Formatter;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Element\MixinElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\MixinElement
 */
class MixinElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \Phug\Formatter\Util\PhpUnwrap::<public>
     * @covers \Phug\Formatter\AbstractFormat::getMixinAttributes
     * @covers \Phug\Formatter\AbstractFormat::formatMixinElement
     * @covers ::<public>
     */
    public function testMixinElement()
    {
        $mixin = new MixinElement();
        $mixin->setName('tabs');
        $tabs = new AttributeElement('tabs', null);
        $tabs->setIsVariadic(true);
        $mixin->getAttributes()->attach($tabs);
        $div = new MarkupElement('div');
        $expression = new ExpressionElement('$__pug_children(get_defined_vars())');
        $expression->uncheck();
        $expression->preventFromTransformation();
        $div->appendChild($expression);
        $mixin->appendChild($div);

        $formatter = new Formatter();
        $php = $formatter->format($mixin);
        $call = '<?php $__pug_mixins["tabs"]('.
            '[], [[false, "a"], [false, "b"]], [], '.
            'function () { echo "block"; }'.
            '); ?>';

        ob_start();
        eval('?>'.$php.$call);
        $html = ob_get_contents();
        ob_end_clean();

        self::assertSame('<div>block</div>', $html);
    }
}
