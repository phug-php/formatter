<?php

namespace Phug\Formatter\Partial;

trait MarkupTrait
{
    public function belongsTo(array $tagList)
    {
        if (is_string($this->getName())) {
            return in_array(strtolower($this->getName()), $tagList);
        }

        return false;
    }
}
