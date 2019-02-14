<?php

namespace packages\base\router;
use packages\base\Exception;

class RuleException extends Exception {


	/**
	 * @param packages\base\router\rule $rule
	 * @param string $message
	 */
	public function __construct(rule $rule, string $message = ""){
		parent::__construct($message);
		$this->rule = $rule;
	}

	/**
	 * Getter for rule
	 * 
	 * @return packages\base\router\rule 
	 */
	public function getRule(): rule {
		return $this->rule;
	}
}