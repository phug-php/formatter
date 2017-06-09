<?php

namespace Phug;

use Phug\Util\AbstractModule;
use Phug\Util\ModulesContainerInterface;

class FormatterModule extends AbstractModule implements FormatterModuleInterface
{
    public function injectFormatter(Formatter $formatter)
    {
        return $formatter;
    }

    public function plug(ModulesContainerInterface $parent)
    {
        parent::plug($this->injectFormatter($parent));
    }
}
