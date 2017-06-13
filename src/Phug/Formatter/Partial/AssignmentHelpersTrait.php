<?php

namespace Phug\Formatter\Partial;

trait AssignmentHelpersTrait
{
    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
    protected function provideAttributeAssignment()
    {
        return $this->provideHelper('attribute_assignment', [
            'attribute_assignments',
            function ($attributeAssignments) {
                return function (&$attributes, $name, $value) use ($attributeAssignments) {
                    if (isset($name) && $name !== '') {
                        $attributes[$name] = $attributeAssignments($attributes, $name, $value);
                    }
                };
            },
        ]);
    }

    /**
     * @return $this
     */
    protected function provideStandAloneAttributeAssignment()
    {
        return $this->provideHelper('stand_alone_attribute_assignment', [
            'attribute_assignment',
            function ($attributeAssignment) {
                return function ($name, $value) use ($attributeAssignment) {
                    $attributes = [];
                    $attributeAssignment($attributes, $name, $value);

                    return $attributes[$name];
                };
            },
        ]);
    }

    /**
     * @return $this
     */
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
                            $code .= $value === true
                                ? $pattern($booleanPattern, $name, $name)
                                : $pattern($attributePattern, $name, $value);
                        }
                    }

                    return $code;
                };
            },
        ]);
    }

    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
    protected function provideStandAloneClassAttributeAssignment()
    {
        return $this->provideHelper('stand_alone_class_attribute_assignment', [
            'class_attribute_assignment',
            function ($classAttributeAssignment) {
                return function ($value) use ($classAttributeAssignment) {
                    $attributes = [];

                    return $classAttributeAssignment($attributes, $value);
                };
            },
        ]);
    }

    /**
     * @return $this
     */
    protected function provideStandAloneStyleAttributeAssignment()
    {
        return $this->provideHelper('stand_alone_style_attribute_assignment', [
            'style_attribute_assignment',
            function ($styleAttributeAssignment) {
                return function ($value) use ($styleAttributeAssignment) {
                    $attributes = [];

                    return $styleAttributeAssignment($attributes, $value);
                };
            },
        ]);
    }
}
