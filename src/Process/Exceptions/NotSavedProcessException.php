<?php

namespace packages\base\Process\Exceptions;

use packages\base\Exception;

class NotSavedProcessException extends Exception
{
    public function __construct(string $message = 'process does not saved')
    {
        parent::__construct($message);
    }
}
