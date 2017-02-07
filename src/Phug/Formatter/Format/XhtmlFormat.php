<?php

namespace Phug\Formatter\Format;

use Phug\Formatter;

abstract class XhtmlFormat extends HtmlFormat
{
    const DOCTYPE = '<!DOCTYPE %s PUBLIC "%s" "%s">';
    const DOCTYPE_LANGUAGE = 'html';
    const SELF_CLOSING_TAG = '<%s />';

    public function __construct(Formatter $formatter)
    {
        parent::__construct($formatter);
        $this->setOptions([
            'doctype_language' => static::DOCTYPE_LANGUAGE,
            'doctype_dtd'      => static::DOCTYPE_DTD,
            'doctype_dtd_url'  => static::DOCTYPE_DTD_URL,
        ]);
        $this->setOption('doctype', $this->pattern(
            'doctype',
            $this->pattern('doctype_language'),
            $this->pattern('doctype_dtd'),
            $this->pattern('doctype_dtd_url')
        ));
    }
}
