<?php

namespace Phug;

use Phug\Formatter\FormatInterface;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\Format\XmlFormat;
use Phug\Formatter\Format\XhtmlFormat;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;

class Formatter implements OptionInterface
{
    use OptionTrait;

    /**
     * Creates a new formatter instance.
     *
     * The formatter will turn DocumentNode tree into StringOfPhtml
     *
     * @param array|null $options the options array
     * @throws ParserException
     */
    public function __construct(array $options = null)
    {

        $this->options = array_replace_recursive([], $options ?: []);
    }

    public function format(ElementInterface $element, $format)
    {

        if (!is_a($format, FormatInterface::class, true)) {
            throw new \InvalidArgumentException(
                "Passed format handler needs to implement ".FormatInterface::class
            );
        }

        if (!($format instanceof FormatInterface)) {
            $format = new $format();
        }

        return $format($element);
    }
}
