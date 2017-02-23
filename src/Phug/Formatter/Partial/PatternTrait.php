<?php

namespace Phug\Formatter\Partial;

trait PatternTrait
{
    use HelperTrait;

    protected function patternName($name)
    {
        return 'pattern.'.$name;
    }

    /**
     * @param $name
     * @param $pattern
     *
     * @return AbstractFormat
     */
    public function addPattern($name, $pattern)
    {
        if (is_array($pattern)) {
            return $this->provideHelper($this->patternName($name), $pattern);
        }

        $this->registerHelper('patterns.'.$name, $pattern);

        return $this->provideHelper($this->patternName($name), [
            'pattern',
            'patterns.'.$name,
            function ($proceed, $pattern) {
                return function () use ($proceed, $pattern) {
                    $args = func_get_args();
                    array_unshift($args, $pattern);

                    return call_user_func_array($proceed, $args);
                };
            },
        ]);
    }

    /**
     * @param $patterns
     *
     * @return $this
     */
    public function addPatterns($patterns)
    {
        foreach ($patterns as $name => $pattern) {
            $this->addPattern($name, $pattern);
        }

        return $this;
    }

    /**
     * @param $name
     *
     * @return string
     */
    public function exportHelper($name)
    {
        $this->requireHelper($name);

        return $this->formatter->getDependencyStorage(
            $this->helperName($name)
        );
    }
}
