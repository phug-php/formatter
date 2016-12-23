<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractValueElement;
use Phug\Util\Partial\EscapeTrait;

class TextElement extends AbstractValueElement
{
    use EscapeTrait;
}
