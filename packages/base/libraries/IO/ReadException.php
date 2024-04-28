<?php

namespace packages\base\IO;

class ReadException extends Exception
{
    public function __construct(File $file)
    {
        parent::__construct($file, 'cannot read the file');
    }
}