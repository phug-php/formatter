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
     */
    public function __construct(array $options = null)
    {

        $this->setOptionsRecursive($options ?: []);
    }

    /**
     * 
     * @param ElementInterface $element the element to format such as a DocumentElement
     * @param FormatInterface  $format  format instance or format class name to use to format like HtmlFormat
     *
     * @return StringOfPhtml
     *
     * @throws FormatterException
     */
    public function format()
    {

        $arguments = new UnOrderedArguments(func_get_args());

        $element = $arguments->required(ElementInterface::class);
        $format = $arguments->required(FormatInterface::class);

        $arguments->noMoreArguments();

        if (!($format instanceof FormatInterface)) {
            $format = new $format($this->getOptions());
        }

        return $format($element);
    }
}
