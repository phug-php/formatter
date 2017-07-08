<?php

namespace Phug\Formatter\Element;

use Phug\Formatter\AbstractElement;
use Phug\Formatter\MarkupInterface;
use SplObjectStorage;

abstract class AbstractMarkupElement extends AbstractElement implements MarkupInterface
{
    private $assignments;

    /**
     * Return true if the tag name is in the given list.
     *
     * @param array $tagList
     *
     * @return bool
     */
    public function belongsTo(array $tagList)
    {
        if (is_string($this->getName())) {
            return in_array(strtolower($this->getName()), $tagList);
        }

        return false;
    }

    /**
     * Add assignment to the markup.
     *
     * @param AssignmentElement $element
     *
     * @return $this
     */
    public function addAssignment(AssignmentElement $element)
    {
        $element->setMarkup($this);
        $this->getAssignments()->attach($element);

        return $this;
    }

    /**
     * Remove an assignment from the markup.
     *
     * @param AssignmentElement $element
     *
     * @return $this
     */
    public function removedAssignment(AssignmentElement $element)
    {
        $this->getAssignments()->detach($element);

        return $this;
    }

    /**
     * Return markup assignments list.
     *
     * @return SplObjectStorage[AssignmentElement]
     */
    public function getAssignments()
    {
        if (!$this->assignments) {
            $this->assignments = new SplObjectStorage();
        }

        return $this->assignments;
    }

    /**
     * Return markup assignments list of a specific name.
     *
     * @param $name
     *
     * @return AssignmentElement[]
     */
    public function getAssignmentsByName($name)
    {
        $assignments = iterator_to_array($this->getAssignments());

        return array_values(array_filter($assignments, function (AssignmentElement $element) use ($name) {
            return $element->getName() === $name;
        }));
    }
}
