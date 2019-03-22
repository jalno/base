<?php
namespace packages\base\Validator;

use packages\base\InputValidationException;

class StringValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['string'];
	}

	/**
	 * Validate data to be a string.
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
		if (!$data) {
			if (!isset($rule['empty']) or !$rule['empty']) {
				throw new InputValidationException($input);
			}
			if (isset($rule['default'])) {
				return $rule['default'];
			}
			return;
		}
		if (isset($rule['values']) and $rule['values']) {
			if (!in_array($data, $rule['values'])) {
				throw new InputValidationException($input);
			}
		} elseif (isset($rule['regex'])) {
			if (!preg_match($rule['regex'], $data)) {
				throw new InputValidationException($input);
			}
		} else {
			if (!isset($rule['htmlTags']) or !$rule['htmlTags']) {
				$data = htmlentities($data, ENT_IGNORE|ENT_SUBSTITUTE|ENT_DISALLOWED, 'UTF-8');
			}
			if (!isset($rule['multiLine']) or !$rule['multiLine']) {
				$data = str_replace("\n", "", $data);
			}
		}
		return $data;
	}
}
