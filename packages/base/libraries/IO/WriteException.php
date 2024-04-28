<?php

namespace packages\base\IO;

class WriteException extends Exception
{
    private $source;

    public function __construct(File $source, File $dist)
    {
        parent::__construct($dist, 'cannot write the file');
        $this->source = $source;
    }

    public function getSource(): File
    {
        return $this->source;
    }
}
