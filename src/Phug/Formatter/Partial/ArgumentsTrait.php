<?php

namespace Phug\Formatter\Partial;

trait ArgumentsTrait
{
    /**
     * @var array<string>
     */
    protected $arguments = [];

    /**
     * @var string
     */
    protected $variadic = null;

    /**
     * Register an argument name in a mixin declaration.
     *
     * @param string $name argument name
     *
     * @return $this
     */
    public function addArgument($name)
    {
        if (substr($name, 0, 3) === '...') {
            return $this->setVariadic(substr($name, 3));
        }

        $this->arguments[] = $name;

        return $this;
    }

    /**
     * Register an variadic argument name (rest arguments) in a mixin declaration.
     *
     * @param string $name argument name
     *
     * @return $this
     */
    public function setVariadic($name)
    {
        $this->variadic = $name;

        return $this;
    }

    /**
     * Get argument names.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get variadic argument name or null if not set.
     *
     * @return string|null
     */
    public function getVariadic()
    {
        return $this->variadic;
    }
}
