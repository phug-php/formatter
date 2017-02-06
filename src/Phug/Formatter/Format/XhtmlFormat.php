<?php

namespace Phug\Formatter\Format;

abstract class XhtmlFormat extends HtmlFormat
{
    const DOCTYPE = '<!DOCTYPE %s PUBLIC "%s" "%s">';
    const DOCTYPE_LANGUAGE = 'html';
    const SELF_CLOSING_TAG = '<%s />';

    public function __construct(array $options = null)
    {
        parent::__construct($options);
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
