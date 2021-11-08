<?php
namespace packages\base;

class InputValidation extends Exception {
	/** @var string input name */
	protected $input;

	/**
	 * @var string $input
	 * @var string $message
	 */
	public function __construct(string $input, string $message = ""){
		$this->input = $input;
		parent::__construct($message);
	}

	/**
	 * Getter for input value
	 * 
	 * @return string
	 */
	public function getInput(): string {
		return $this->input;
	}

	/**
	 * Setter for input value
	 * 
	 * @param string $input
	 * @return string
	 */
	public function setInput(string $input): void {
		$this->input = $input;
	}
}
