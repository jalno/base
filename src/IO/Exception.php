<?php

namespace packages\base\IO;

class Exception extends \Exception
{
    protected $targetFile;

    public function __construct($file, $message = '')
    {
        $this->targetFile = $file;
        $this->message = $message;
    }

    public function getTargetFile(): File
    {
        return $this->targetFile;
    }
}
