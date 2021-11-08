<?php
namespace packages\base\Validator;

use packages\base\InputValidationException;

class BooleanValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['bool'];
	}

	/**
	 * Validate data to be a boolean value.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return mixed|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		if (!is_string($data)) {
			throw new InputValidationException($input);
		}
		return !($data === "0" || $data === "false" || $data === "");
	}
}
