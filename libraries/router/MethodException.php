<?php
namespace packages\base\router;
use packages\base\Exception;

class MethodException extends Exception {
	/** @var string wrong method */
	private $method;

	/**
	 * 
	 */
	public function __construct(string $method, string $message = "method is invalid") {
		$this->method = $method;
		parent::__construct($message);
	}

	/**
	 * Getter for wrong method
	 * 
	 * @return string
	 */
	public function getMethod(): string {
		return $this->method;
	}
}
