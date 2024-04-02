<?php
namespace packages\base\router;

class PathException extends RouterRuleException {
	public function __construct(Rule $rule, private mixed $path, string $message) {
		parent::__construct($rule, $message);
	}

	/**
	 * Getter for wrong path
	 * 
	 * @return mixed
	 */
	public function getPath() {
		return $this->path;
	}
}
