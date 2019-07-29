<?php

namespace Phug\Test\Element;

use PHPUnit\Framework\TestCase;
use Phug\Formatter;
use Phug\Formatter\Element\CodeElement;

/**
 * @coversDefaultClass \Phug\Formatter\Element\CodeElement
 */
class CodeElementTest extends TestCase
{
    /**
     * @covers ::<public>
     */
    public function testCodeElement()
    {
        $foo = new CodeElement('$foo');

        self::assertSame('$foo', $foo->getValue());
    }

    public function testHooks()
    {
        $code = new CodeElement('$foo = 9;');
        $code->setPreHook('$__eachScopeVariables = [\'foo\' => isset($foo) ? $foo : null];');
        $code->setPostHook('extract($__eachScopeVariables);');
        $formatter = new Formatter();

        ob_start();
        $php = $formatter->format($code);
        eval('?>'.$formatter->formatDependencies().$php);
        ob_end_clean();

        self::assertNull($foo);

        $code = new CodeElement('$foo = 9;');
        $formatter = new Formatter();

        ob_start();
        $php = $formatter->format($code);
        eval('?>'.$formatter->formatDependencies().$php);
        ob_end_clean();

        self::assertSame(9, $foo);

        $code = new CodeElement('$foo = 9;');
        $code->setPreHook('$__eachScopeVariables = [\'foo\' => isset($foo) ? $foo : null];');
        $code->setPostHook('extract($__eachScopeVariables);');
        $formatter = new Formatter();

        ob_start();
        $foo = 42;
        $php = $formatter->format($code);
        eval('?>'.$formatter->formatDependencies().$php);
        ob_end_clean();

        self::assertSame(42, $foo);
    }
}
