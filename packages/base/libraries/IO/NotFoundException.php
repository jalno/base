<?php

namespace packages\base\IO;

class NotFoundException extends Exception
{
    public function __construct($file)
    {
        parent::__construct($file, 'cannot find the IO resource');
    }
}