<?php

$pugModule['CompileAttribute'] = function (&$attributes, $name, $value) {
    switch ($name) {
        case 'class':
            $classes = isset($attributes['class']) ? array_filter(explode(' ', $attributes['class'])) : [];
            foreach ((array) $value as $input) {
                foreach (explode(' ', strval($input)) as $class) {
                    if (!in_array($class, $classes)) {
                        $classes[] = $class;
                    }
                }
            }
            $value = implode(' ', $classes);
            break;
        case 'style':
            $styles = isset($attributes['style']) ? array_filter(explode(' ', $attributes['style'])) : [];
            foreach ((array) $value as $propertyName => $propertyValue) {
                if (!is_int($propertyName)) {
                    $propertyValue = $propertyName.':'.$propertyValue;
                }
                $styles[] = $propertyValue;
            }
            $value = implode(';', $styles);
            break;
    }

    $attributes[$name] = $value;
};
