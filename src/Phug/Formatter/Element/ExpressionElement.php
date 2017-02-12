<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractValueElement;
use Phug\Util\Partial\CheckTrait;
use Phug\Util\Partial\EscapeTrait;

class ExpressionElement extends AbstractValueElement
{
    use CheckTrait;
    use EscapeTrait;
}
