<?php

namespace Phug\Test;

use Phug\Formatter;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Element\MarkupElement;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testFormat()
    {

        $img = new MarkupElement('img');
        $formatter = new Formatter();

        $this->assertSame('<img>', $formatter->format($img, HtmlFormat::class));
    }
}
