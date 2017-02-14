<?php

namespace Phug\Formatter\Partial;

use SplObjectStorage;

trait MarkupTrait
{
    private $assignments;

    public function belongsTo(array $tagList)
    {
        if (is_string($this->getName())) {
            return in_array(strtolower($this->getName()), $tagList);
        }

        return false;
    }

    public function addAssignment(AssignmentElement $element)
    {
        $this->getAssignments()->attach($element);
    }

    public function getAssignments()
    {
        if (!$this->assignments) {
            $this->assignments = new SplObjectStorage();
        }

        return $this->assignments;
    }
}
