<?php
namespace packages\base\Process\Exceptions;

use packages\base\Exception;
use packages\base\Process;

class NotStartedProcessException extends Exception {
	public function __construct(public readonly Process $process, string $message = "process does not started") {
		parent::__construct($message);
	}

	public function getProcess(): Process
	{
		return $this->process;
	}
}
