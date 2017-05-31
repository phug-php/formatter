<?php

namespace Phug\Formatter\Format;

class HtmlFormat extends XhtmlFormat
{
    const DOCTYPE = '<!DOCTYPE html>';
    const SELF_CLOSING_TAG = '<%s>';
    const BOOLEAN_ATTRIBUTE_PATTERN = ' %s';
}
