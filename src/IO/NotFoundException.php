<?php

namespace packages\base\IO;

class NotFoundException extends Exception
{
    public function __construct($file, string $message = "Cannot find the IO resource")
    {
        parent::__construct($file, $message);
    }
}
