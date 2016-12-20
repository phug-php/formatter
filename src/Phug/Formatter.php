<?php

namespace Phug;

use Phug\Formatter\ElementInterface;
use Phug\Formatter\FormatInterface;
use Phug\Util\OptionInterface;
use Phug\Util\Partial\OptionTrait;
use Phug\Util\UnorderedArguments;

class Formatter implements OptionInterface
{
    use OptionTrait;

    /**
     * Creates a new formatter instance.
     *
     * The formatter will turn DocumentNode tree into StringOfPhtml
     *
     * @param array|null $options the options array
     */
    public function __construct(array $options = null)
    {
        $this->setOptionsRecursive($options ?: []);
    }

    /**
     * Entry point of the Formatter, typically waiting for a DocumentElement and
     * a format, to return a string with HTML and PHP nested.
     *
     * @param ElementInterface $element the element to format such as a DocumentElement
     * @param FormatInterface  $format  format instance or format class name to use to format like HtmlFormat
     *
     * @throws FormatterException
     *
     * @return StringOfPhtml
     */
    public function format()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $element = $arguments->required(ElementInterface::class);
        $format = $arguments->required(FormatInterface::class);

        $arguments->noMoreArguments();

        if (!($format instanceof FormatInterface)) {
            $format = new $format($this->getOptions());
        }

        return $format($element);
    }
}
