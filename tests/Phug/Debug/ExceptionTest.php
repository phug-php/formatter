<?php

namespace Phug\Test\Debug;

use Phug\Debug\Exception;
use Phug\Formatter;
use Phug\Formatter\Element\DocumentElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Parser\Node\ExpressionNode;

/**
 * @coversDefaultClass \Phug\Debug\Exception
 */
class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     * @covers \Phug\Formatter::storeDebugNode
     * @covers \Phug\Formatter::getDebugError
     */
    public function testFormat()
    {
        $formatter = new Formatter([
            'debug' => true,
        ]);
        $node = new ExpressionNode(
            3,
            15,
            null,
            null,
            null,
            null,
            'source.pug'
        );
        $document = new DocumentElement();
        $document->appendChild($htmlEl = new MarkupElement('html'));
        $htmlEl->appendChild($bodyEl = new MarkupElement('body'));
        $bodyEl->appendChild(new ExpressionElement(
            '12 / 0',
            $node
        ));
        $php = $formatter->format($document);
        $php = $formatter->formatDependencies().$php;

        $error = null;
        ob_start();
        try {
            eval('?>'.$php);
        } catch (\Exception $e) {
            $error = $formatter->getDebugError($e, $php);
        }
        ob_end_clean();

        self::assertInstanceOf(Exception::class, $error);
        self::assertSame(3, $error->getPugLine());
        self::assertSame(15, $error->getPugOffset());
        self::assertSame('source.pug', $error->getPugFile());
    }
}
