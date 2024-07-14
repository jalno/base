<?php

namespace packages\base;

/**
 * @deprecated use packages\base\Process\Exceptions\NotShellAccessException instead!
 */
class NotShellAccess extends Exception
{
    public function __construct(string $message = 'shell_exec() function is disabled')
    {
        parent::__construct($message);
    }
}
