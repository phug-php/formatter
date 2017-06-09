<?php

namespace Phug;

use Phug\Util\ModuleInterface;

interface FormatterModuleInterface extends ModuleInterface
{
    public function injectFormatter(Formatter $formatter);
}
