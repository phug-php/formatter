<?php

namespace Phug\Formatter\Partial;

trait AssignmentHelpersTrait
{
    protected function provideAttributeAssignments()
    {
        return $this->provideHelper('attribute_assignments', [
            'available_attribute_assignments',
            'get_helper',
            function ($availableAssignments, $getHelper) {
                return function (&$attributes, $name, $value) use ($availableAssignments, $getHelper) {
                    if (!in_array($name, $availableAssignments)) {
                        return $value;
                    }

                    $helper = $getHelper($name.'_attribute_assignment');

                    return $helper($attributes, $value);
                };
            },
        ]);
    }

    protected function provideAttributeAssignment()
    {
        return $this->provideHelper('attribute_assignment', [
            'attribute_assignments',
            function ($attributeAssignments) {
                return function (&$attributes, $name, $value) use ($attributeAssignments) {
                    $attributes[$name] = $attributeAssignments($attributes, $name, $value);
                };
            },
        ]);
    }

    protected function provideAttributesAssignment()
    {
        return $this->provideHelper('attributes_assignment', [
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
        ]);
    }

    protected function provideClassAttributeAssignment()
    {
        return $this->addAttributeAssignment('class', function (&$attributes, $value) {
            $classes = isset($attributes['class']) ? array_filter(explode(' ', $attributes['class'])) : [];
            foreach ((array) $value as $input) {
                foreach (explode(' ', strval($input)) as $class) {
                    if (!in_array($class, $classes)) {
                        $classes[] = $class;
                    }
                }
            }

            return implode(' ', $classes);
        });
    }

    protected function provideStyleAttributeAssignment()
    {
        return $this->addAttributeAssignment('style', function (&$attributes, $value) {
            $styles = isset($attributes['style']) ? array_filter(explode(';', $attributes['style'])) : [];
            foreach ((array) $value as $propertyName => $propertyValue) {
                if (!is_int($propertyName)) {
                    $propertyValue = $propertyName.':'.$propertyValue;
                }
                $styles[] = $propertyValue;
            }

            return implode(';', $styles);
        });
    }
}
