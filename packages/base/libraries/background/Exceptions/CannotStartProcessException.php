<?php
namespace packages\base\Process\Exceptions;

use packages\base\Exception;
use packages\base\Process;

class CannotStartProcessException extends Exception {
	public function __construct(public readonly Process $process, string $message = "can not start process") {
		parent::__construct($message);
	}

	public function getProcess(): Process
	{
		return $this->process;
	}
}
