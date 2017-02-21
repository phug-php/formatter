<?php

$pugModule['CompileAttributes'] = function () {
    global $pugModule;

    $attributes = [];
    foreach (func_get_args() as $input) {
        foreach ($input as $name => $value) {
            $pugModule['CompileAttribute']($attributes, $name, $value);
        }
    }
    $code = '';

    return $code;
};
