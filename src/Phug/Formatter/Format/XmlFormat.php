<?php

namespace Phug\Formatter\Format;

use Phug\Formatter;
use Phug\Formatter\AbstractFormat;
use Phug\Formatter\Element\AssignmentElement;
use Phug\Formatter\Element\AttributeElement;
use Phug\Formatter\Element\ExpressionElement;
use Phug\Formatter\Element\MarkupElement;
use Phug\Formatter\ElementInterface;
use Phug\Formatter\MarkupInterface;
use Phug\FormatterException;

class XmlFormat extends AbstractFormat
{
    const DOCTYPE = '<?xml version="1.0" encoding="utf-8" ?>';
    const OPEN_PAIR_TAG = '<%s>';
    const CLOSE_PAIR_TAG = '</%s>';
    const SELF_CLOSING_TAG = '<%s />';
    const ATTRIBUTE_PATTERN = ' %s="%s"';
    const BOOLEAN_ATTRIBUTE_PATTERN = ' %s="%s"';
    const BUFFER_VARIABLE = '$__value';
    const SAVE_VALUE = '%s=%s';
    const TEST_VALUE = 'isset(%s)';

    public function __construct(Formatter $formatter)
    {
        $this->setOptions([
            'assignment_handlers'   => [],
            'attribute_assignments' => [],
        ]);

        parent::__construct($formatter);

        $this
            ->registerHelper('available_attribute_assignments', [])
            ->addPatterns([
                'open_pair_tag'             => static::OPEN_PAIR_TAG,
                'close_pair_tag'            => static::CLOSE_PAIR_TAG,
                'self_closing_tag'          => static::SELF_CLOSING_TAG,
                'attribute_pattern'         => static::ATTRIBUTE_PATTERN,
                'boolean_attribute_pattern' => static::BOOLEAN_ATTRIBUTE_PATTERN,
                'save_value'                => static::SAVE_VALUE,
                'test_value'                => static::TEST_VALUE,
                'buffer_variable'           => static::BUFFER_VARIABLE,
            ])
            ->provideHelper('attribute_assignments', [
                'available_attribute_assignments',
                function ($availableAssignments) {
                    return function (&$attributes, $name, $value) use ($availableAssignments) {
                        /**
                         * @var array $pugModule
                         */
                        return in_array($name, $availableAssignments)
                            ? $pugModule[$name.'_attribute_assignment']($attributes, $value)
                            : $value;
                    };
                },
            ])
            ->provideHelper('attribute_assignment', [
                'attribute_assignments',
                function ($attributeAssignments) {
                    return function (&$attributes, $name, $value) use ($attributeAssignments) {
                        $attributes[$name] = $attributeAssignments($attributes, $name, $value);
                    };
                },
            ])
            ->provideHelper('attributes_assignment', [
                'attribute_assignment',
                'pattern',
                'pattern.attribute_pattern',
                'pattern.boolean_attribute_pattern',
                function ($attributeAssignment, $pattern, $attributePattern, $booleanPattern) {
                    return function () use ($attributeAssignment, $pattern, $attributePattern, $booleanPattern) {
                        $attributes = [];
                        foreach (func_get_args() as $input) {
                            foreach ($input as $name => $value) {
                                $attributeAssignment($attributes, $name, $value);
                            }
                        }
                        $code = '';
                        foreach ($attributes as $name => $value) {
                            if ($value) {
                                $code .= $pattern(
                                    $value === true ? $booleanPattern : $attributePattern,
                                    $name,
                                    $value
                                );
                            }
                        }

                        return $code;
                    };
                },
            ])
            ->addAttributeAssignment('class', function (&$attributes, $value) {
                $classes = isset($attributes['class']) ? array_filter(explode(' ', $attributes['class'])) : [];
                foreach ((array) $value as $input) {
                    foreach (explode(' ', strval($input)) as $class) {
                        if (!in_array($class, $classes)) {
                            $classes[] = $class;
                        }
                    }
                }

                return implode(' ', $classes);
            })
            ->addAttributeAssignment('style', function (&$attributes, $value) {
                $styles = isset($attributes['style']) ? array_filter(explode(' ', $attributes['style'])) : [];
                foreach ((array) $value as $propertyName => $propertyValue) {
                    if (!is_int($propertyName)) {
                        $propertyValue = $propertyName.':'.$propertyValue;
                    }
                    $styles[] = $propertyValue;
                }

                return implode(';', $styles);
            });

        $handlers = $this->getOption('attribute_assignments');
        foreach ($handlers as $name => $handler) {
            $this->addAttributeAssignment($name, $handler);
        }
    }

    protected function addAttributeAssignment($name, $handler)
    {
        $availableAssignments = $this->getHelper('available_attribute_assignments');
        $this->addPattern($name.'_attribute_assignment', function () use ($handler) {
            return $handler;
        });
        $availableAssignments[] = $name;

        return $this->registerHelper('available_attribute_assignments', $availableAssignments);
    }

    public function __invoke(ElementInterface $element)
    {
        return $this->format($element);
    }

    protected function isSelfClosingTag(MarkupInterface $element)
    {
        return !$element->hasChildren();
    }

    protected function isBlockTag(MarkupInterface $element)
    {
        return true;
    }

    protected function formatAttributeElement(AttributeElement $element)
    {
        $value = $element->getValue();
        $name = $element->getName();
        if ($value instanceof ExpressionElement) {
            if (strtolower($value->getValue()) === 'true') {
                $formattedValue = null;
                if ($name instanceof ExpressionElement) {
                    $bufferVariable = $this->pattern('buffer_variable');
                    $name = $this->pattern(
                        'php_display_code',
                        $this->pattern(
                            'save_value',
                            $bufferVariable,
                            $this->formatCode($name->getValue(), $name->isChecked())
                        )
                    );
                    $value = new ExpressionElement($bufferVariable);
                    $formattedValue = $this->format($value);
                }
                $formattedName = $this->format($name);
                $formattedValue = $formattedValue ?: $formattedName;

                return $this->pattern(
                    'boolean_attribute_pattern',
                    $formattedName,
                    $formattedValue
                );
            }
            if (in_array(strtolower($value->getValue()), ['false', 'null', 'undefined'])) {
                return '';
            }
        }

        return $this->pattern(
            'attribute_pattern',
            $this->format($name),
            $this->format($value)
        );
    }

    protected function formatPairTag($pattern, MarkupElement $element)
    {
        return sprintf(
            $pattern,
            $element->hasChildren()
                ? sprintf(
                    $this->isBlockTag($element)
                        ? $this->getNewLine().'%s'.$this->getIndent()
                        : '%s',
                    $this->formatElementChildren($element)
                )
                : ''
        );
    }

    protected function formatAssignmentValue($value)
    {
        if ($value instanceof ExpressionElement) {
            return $value->getValue();
        }

        return var_export(strval($value), true);
    }

    protected function formatAttributeAsArrayItem(AttributeElement $attribute)
    {
        return $this->formatAssignmentValue($attribute->getName()).
            ' => '.
            $this->formatAssignmentValue($attribute->getValue());
    }

    protected function formatAssignmentElement(AssignmentElement $element)
    {
        $handlers = $this->getOption('assignment_handlers');
        $newElements = [];
        foreach ($handlers as $handler) {
            $iterator = $handler($element) ?: [];
            foreach ($iterator as $newElement) {
                $newElements[] = $newElement;
            }
        }

        $markup = $element->getMarkup();
        $attributesAssignments = $markup->getAssignmentsByName('attributes');

        $arguments = [];
        foreach ($attributesAssignments as $attributesAssignment) {
            /**
             * @var AssignmentElement $attributesAssignment
             */
            foreach ($attributesAssignment->getAttributes() as $attribute) {
                $arguments[] = $attribute->getValue();
            }
            $markup->removedAssignment($attributesAssignment);
        }

        $attributes = $markup->getAttributes();

        if ($attributes->count()) {
            $arguments[] = '['.implode(
                ', ',
                array_map(
                    [$this, 'formatAttributeAsArrayItem'],
                    iterator_to_array($attributes)
                )
            ).']';
            $attributes->removeAll($attributes);
        }

        $assignments = $markup->getAssignments();
        foreach ($assignments as $assignment) {
            throw new FormatterException(
                'Unable to handle '.$assignment->getName().' assignment'
            );
        }

        $newElements[] = new ExpressionElement($this->exportHelper('attributes_assignment').'('.implode(', ', $arguments).')');

        return implode('', array_map([$this, 'format'], $newElements));
    }

    protected function formatAttributes(MarkupElement $element)
    {
        $code = '';
        $assignments = $element->getAssignments();
        foreach ($assignments as $assignment) {
            return $this->format($assignment);
        }

        foreach ($element->getAttributes() as $attribute) {
            $code .= $this->format($attribute);
        }

        return $code;
    }

    protected function formatMarkupElement(MarkupElement $element)
    {
        $tag = $this->format($element->getName());
        $attributes = $this->formatAttributes($element);

        if ($this->isSelfClosingTag($element)) {
            return $this->pattern('self_closing_tag', $tag.$attributes);
        }

        return sprintf(
            $this->isBlockTag($element)
                ? $this->getIndent().'%s'.$this->getNewLine()
                : '%s',
            $this->formatPairTag(
                (
                    $this->pattern('open_pair_tag', $tag.$attributes).
                    '%s'.
                    $this->pattern('close_pair_tag', $tag)
                ),
                $element
            )
        );
    }
}
