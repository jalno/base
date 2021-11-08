<?php
namespace packages\base;

class NotShellAccess extends Exception {
	public function __construct(string $message = "shell_exec() function is disiabled") {
		parent::__construct($message);
	}
}
