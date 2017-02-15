<?php

$moduleExports = function () {
    $attributes = [];
    foreach (func_get_args() as $input) {
        foreach ($input as $name => $value) {
            $require('CompileAttribute')($attributes, $name, $value);
        }
    }
    $code = '';

    return $code;
};