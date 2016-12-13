<?php

namespace Phug\Test\Format;

use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\Format\HtmlFormat;

/**
 * @coversDefaultClass \Phug\Formatter\Format\HtmlFormat
 */
class HtmlFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::<public>
     */
    public function testHtmlFormat()
    {
        $img = new MarkupElement('img', ['src' => '/foo/bar.png']);
        $htmlFormat = new HtmlFormat();

        $this->assertSame('<img>', $htmlFormat($img));
    }
}
