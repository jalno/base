<?php

namespace packages\base\Process\Exceptions;

use packages\base\Exception;
use packages\base\Process;

class NotStartedProcessException extends Exception
{
    protected Process $process;

    public function __construct(Process $process, string $message = 'process does not started')
    {
        parent::__construct($message);
        $this->process = $process;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
