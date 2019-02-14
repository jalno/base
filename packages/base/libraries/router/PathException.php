<?php
namespace packages\base\router;

class PathException extends RouterRuleException {
	/** @var mixed wrong path */
	private $path;

	/**
	 * @param mixed $path
	 * @param string $message
	 */
	public function __construct($path, string $message){
		$this->path = $path;
		parent::__construct($message);
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
