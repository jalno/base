<?php

namespace packages\base\Frontend;

class SourceAutoloaderFileException extends \Exception
{
    private $classfile;
    private $source;

    public function __construct($classfile, $source)
    {
        $this->classfile = $classfile;
        $this->source = $source;
    }

    public function getClassFile()
    {
        return $this->classfile;
    }

    public function getSource()
    {
        return $this->source;
    }
}
