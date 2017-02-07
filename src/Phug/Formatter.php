<?php

namespace Phug;

// Elements
use Phug\Formatter\ElementInterface;
// Formats
use Phug\Formatter\Format\BasicFormat;
use Phug\Formatter\Format\FramesetFormat;
use Phug\Formatter\Format\HtmlFormat;
use Phug\Formatter\Format\MobileFormat;
use Phug\Formatter\Format\OneDotOneFormat;
use Phug\Formatter\Format\PlistFormat;
use Phug\Formatter\Format\StrictFormat;
use Phug\Formatter\Format\TransitionalFormat;
use Phug\Formatter\Format\XmlFormat;
use Phug\Formatter\FormatInterface;
// Utils
use Phug\Util\OptionInterface;
use Phug\Util\Partial\LevelTrait;
use Phug\Util\Partial\OptionTrait;
use Phug\Util\UnorderedArguments;

class Formatter implements OptionInterface
{
    use LevelTrait;
    use OptionTrait;

    /**
     * @var FormatInterface
     */
    private $format;

    /**
     * Creates a new formatter instance.
     *
     * The formatter will turn DocumentNode tree into StringOfPhtml
     *
     * @param array|null $options the options array
     */
    public function __construct(array $options = null)
    {
        $this->setOptionsRecursive([
            'default_format' => BasicFormat::class,
            'formats'        => [
                'basic'        => BasicFormat::class,
                'frameset'     => FramesetFormat::class,
                'html'         => HtmlFormat::class,
                'mobile'       => MobileFormat::class,
                '1.1'          => OneDotOneFormat::class,
                'plist'        => PlistFormat::class,
                'strict'       => StrictFormat::class,
                'transitional' => TransitionalFormat::class,
                'xml'          => XmlFormat::class,
            ],
        ], $options ?: []);

        $formatClassName = $this->getOption('default_format');

        if (!is_a($formatClassName, FormatInterface::class, true)) {
            throw new RuntimeException(
                "Passed default format class $formatClassName must ".
                'implement '.FormatInterface::class
            );
        }

        // Throw exception if a format is wrong.
        foreach ($this->getOption('formats') as $doctype => $format) {
            $this->setFormatHandler($doctype, $format);
        }

        $this->format = $formatClassName;
    }

    /**
     * Set the node compiler for a givent node class name.
     *
     * @param string                       node class name
     * @param NodeCompilerInterface|string handler
     *
     * @return $this
     */
    public function setFormatHandler($doctype, $format)
    {
        if (!is_a($format, FormatInterface::class, true)) {
            throw new \InvalidArgumentException(
                "Passed default format class $format must ".
                'implement '.FormatInterface::class
            );
        }
        $this->setOption(['formats', $doctype], $format);

        return $this;
    }

    /**
     * Set a format name as the current or fallback to default if not available.
     *
     * @param string doctype format identifier
     *
     * @return $this
     */
    public function setFormat($doctype)
    {
        $formats = $this->getOption('formats');
        $this->format = empty($formats[$doctype])
            ? $this->getOption('default_format')
            : $formats[$doctype];

        return $this;
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
        $format = $arguments->optional(FormatInterface::class) ?: $this->format;

        $arguments->noMoreArguments();

        if (!($format instanceof FormatInterface)) {
            $format = new $format($this);
        }

        return $format($element);
    }
}
