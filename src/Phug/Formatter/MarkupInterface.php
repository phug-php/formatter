<?php

namespace Phug\Formatter;

use Phug\Formatter\Element\AssignmentElement;

interface MarkupInterface extends ElementInterface
{
    public function belongsTo(array $tagList);

    public function getName();

    public function addAssignment(AssignmentElement $element);

    public function removedAssignment(AssignmentElement $element);

    public function getAssignments();

    public function getAssignmentsByName($name);

    public function isAutoClosed();
}
