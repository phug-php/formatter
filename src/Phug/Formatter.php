<?php

namespace Phug;

// Elements
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
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
     * @var FormatInterface|string
     */
    private $format;

    /**
     * @var DependencyInjection
     */
    private $dependencies;

    /**
     * Creates a new formatter instance.
     *
     * The formatter will turn DocumentNode tree into a PHTML string
     *
     * @param array|null $options the options array
     */
    public function __construct(array $options = null)
    {
        $this->setOptionsRecursive([
            'dependencies_storage' => 'pugModule',
            'default_format'       => BasicFormat::class,
            'formats'              => [
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
            throw new \RuntimeException(
                "Passed default format class $formatClassName must ".
                'implement '.FormatInterface::class
            );
        }

        // Throw exception if a format is wrong.
        foreach ($this->getOption('formats') as $doctype => $format) {
            $this->setFormatHandler($doctype, $format);
        }

        $this->format = $formatClassName;

        $this->initDependencies();
    }

    /**
     * Set the format handler for a given doctype identifier.
     *
     * @param string                 $doctype doctype identifier
     * @param FormatInterface|string $format  format handler
     *
     * @return $this
     */
    public function setFormatHandler($doctype, $format)
    {
        if (!is_a($format, FormatInterface::class, true)) {
            throw new \InvalidArgumentException(
                "Passed format class $format must ".
                'implement '.FormatInterface::class
            );
        }
        $this->setOption(['formats', $doctype], $format);

        return $this;
    }

    /**
     * Return current format.
     *
     * @return FormatInterface|string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Return current format as instance of FormatInterface.
     *
     * @param FormatInterface|string optional format, if missing current format is used
     *
     * @return FormatInterface
     */
    public function getFormatInstance($format = null)
    {
        $format = $format ?: $this->format;

        if (!($format instanceof FormatInterface)) {
            $format = new $format($this);
        }

        return $format;
    }

    /**
     * Handle PHP code with the pattern php_handle_code.
     *
     * @param string $phpCode
     *
     * @return string
     */
    public function handleCode($phpCode)
    {
        return $this->getFormatInstance()->handleCode($phpCode);
    }

    /**
     * Set a format name as the current or fallback to default if not available.
     *
     * @param string $doctype format identifier
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
     * Create/reset the dependency injector.
     *
     * @return $this
     */
    public function initDependencies()
    {
        $this->dependencies = new DependencyInjection();

        return $this;
    }

    /**
     * Create/reset the dependency injector.
     */
    public function formatDependencies()
    {
        if ($this->dependencies->countRequiredDependencies() === 0) {
            return '';
        }

        $dependenciesExport = $this->dependencies->export(
            $this->getOption('dependencies_storage')
        );

        return $this->format(new CodeElement(trim($dependenciesExport)));
    }

    /**
     * @return DependencyInjection
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @param string $name dependency name
     *
     * @return string
     */
    public function getDependencyStorage($name)
    {
        return $this->dependencies->getStorageItem($name, $this->getOption('dependencies_storage'));
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
     * @return string
     */
    public function format()
    {
        $arguments = new UnorderedArguments(func_get_args());

        $element = $arguments->required(ElementInterface::class);
        $format = $arguments->optional(FormatInterface::class);

        $arguments->noMoreArguments();

        if ($element instanceof DoctypeElement) {
            $formats = $this->getOption('formats');
            $doctype = $element->getValue();
            $this->setFormat($doctype);
            if (isset($formats[$doctype])) {
                $element->setValue(null);
            }
        }

        $format = $this->getFormatInstance($format);

        $format->setFormatter($this);

        return $format($element);
    }
}
