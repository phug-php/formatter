<?php

namespace Phug;

// Elements
use Phug\Formatter\Element\CodeElement;
use Phug\Formatter\Element\DoctypeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\ElementInterface;
// Formats
use Phug\Formatter\Event\DependencyStorageEvent;
use Phug\Formatter\Event\FormatEvent;
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
use Phug\Parser\NodeInterface;
use Phug\Util\Exception\LocatedException;
use Phug\Util\ModuleContainerInterface;
use Phug\Util\Partial\LevelTrait;
use Phug\Util\Partial\ModuleContainerTrait;
use Phug\Util\SourceLocation;

class Formatter implements ModuleContainerInterface
{
    use LevelTrait;
    use ModuleContainerTrait;

    /**
     * @var FormatInterface|string
     */
    private $format;

    /**
     * @var DependencyInjection
     */
    private $dependencies;

    /**
     * @var array
     */
    private $debugNodes = [];

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
            'debug'                       => false,
            'dependencies_storage'        => 'pugModule',
            'dependencies_storage_getter' => null,
            'default_format'              => BasicFormat::class,
            'formats'                     => [
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
            'modules'              => [],

            'on_format'             => null,
            'on_dependency_storage' => null,
        ], $options ?: []);

        $this->dependencies = new DependencyInjection();

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

        if ($onFormat = $this->getOption('on_format')) {
            $this->attach(FormatterEvent::FORMAT, $onFormat);
        }

        if ($onDependencyStorage = $this->getOption('on_dependency_storage')) {
            $this->attach(FormatterEvent::DEPENDENCY_STORAGE, $onDependencyStorage);
        }

        $this->addModules($this->getOption('modules'));
    }

    /**
     * Store a node in a debug list and return the allocated index for it.
     *
     * @param NodeInterface $node
     *
     * @return int
     */
    public function storeDebugNode(NodeInterface $node)
    {
        $id = count($this->debugNodes);
        $this->debugNodes[] = $node;

        return $id;
    }

    /**
     * Return a formatted error linked to pug source.
     *
     * @param \Throwable $error
     * @param string     $code
     * @param string     $renderingFile
     *
     * @throws \Throwable
     *
     * @return LocatedException
     */
    public function getDebugError($error, $code, $renderingFile = null)
    {
        /** @var \Throwable $error */
        $source = explode("\n", $code, $error->getLine());
        array_pop($source);
        $source = implode("\n", $source);
        $pos = mb_strrpos($source, 'PUG_DEBUG:');
        if ($pos === false) {
            throw $error;
        }
        $nodeId = intval(mb_substr($source, $pos + 10, 32));
        if (!isset($this->debugNodes[$nodeId])) {
            throw $error;
        }
        /** @var NodeInterface $node */
        $node = $this->debugNodes[$nodeId];
        $nodeLocation = $node->getSourceLocation();
        $location = new SourceLocation(
            $renderingFile ?: $nodeLocation->getPath(),
            $nodeLocation->getLine(),
            $nodeLocation->getOffset(),
            $nodeLocation->getOffsetLength()
        );

        // @TODO: implement $renderingFile detection for accurate traces

        return new LocatedException(
            $location,
            $error->getMessage(),
            $error->getCode(),
            $error
        );
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
     * Format a code with transform_expression and tokens handlers.
     *
     * @param string $code             input code
     * @param bool   $checked          test variables
     * @param bool   $noTransformation disable transform_expression
     *
     * @return string
     */
    public function formatCode($code, $checked = false, $noTransformation = false)
    {
        return $this->getFormatInstance()->formatCode($code, $checked, $noTransformation);
    }

    /**
     * @param array $attributes
     *
     * @return ExpressionElement
     */
    public function formatAttributesList($attributes)
    {
        return $this->getFormatInstance()->formatAttributesList($attributes);
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
        $dependencyStorage = $this->dependencies->getStorageItem($name, $this->getOption('dependencies_storage'));

        $event = new DependencyStorageEvent($dependencyStorage);
        $this->trigger($event);

        return $event->getDependencyStorage();
    }

    /**
     * Entry point of the Formatter, typically waiting for a DocumentElement and
     * a format, to return a string with HTML and PHP nested.
     *
     * @param ElementInterface     $element
     * @param FormatInterface|null $format
     *
     * @return string
     */
    public function format(ElementInterface $element, $format = null)
    {
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

        $event = new FormatEvent($element, $format);
        $this->trigger($event);

        $element = $event->getElement();
        $format = $event->getFormat();

        if (!$element) {
            return '';
        }

        return $format($element);
    }

    public function getModuleBaseClassName()
    {
        return FormatterModuleInterface::class;
    }
}
