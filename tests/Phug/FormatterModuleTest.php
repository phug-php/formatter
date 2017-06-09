<?php

namespace Phug\Test;

use Phug\Formatter;
use Phug\FormatterModule;

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
        $copy = null;
        $module = new FormatterModule();
        $module->onPlug(function ($_formatter) use (&$copy) {
            $copy = $_formatter;
        });
        $formatter = new Formatter([
            'modules' => [$module],
        ]);
        self::assertSame($formatter, $copy);
    }
}
