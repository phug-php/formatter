<?php

namespace Phug\Debug;

class Exception extends \Exception
{
    /**
     * @var string
     */
    protected $pugFile;

    /**
     * @var int
     */
    protected $pugLine;

    /**
     * @var int
     */
    protected $pugOffset;

    public function __construct($message, $code, $previous, $file, $line, $offset)
    {
        $this->pugFile = $file;
        $this->pugLine = $line;
        $this->pugOffset = $offset;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getPugFile()
    {
        return $this->pugFile;
    }

    /**
     * @return int
     */
    public function getPugLine()
    {
        return $this->pugLine;
    }

    /**
     * @return int
     */
    public function getPugOffset()
    {
        return $this->pugOffset;
    }
}
