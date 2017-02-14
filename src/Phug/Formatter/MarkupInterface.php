<?php

namespace Phug\Formatter;

interface MarkupInterface
{
    public function belongsTo(array $tagList);

    public function getName();

    public function addAssignment(AssignmentElement $element);

    public function getAssignments();
}
