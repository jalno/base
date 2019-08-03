<?php
namespace packages\base\Validator;

use packages\base\InputValidationException;

class URLValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['url'];
	}

	/**
	 * Validate data to be a URL.
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
		} else {
			$url = parse_url($data);
			if (!$url) {
				throw new InputValidationException($input);
			}
			if (!array_key_exists('protocols', $rule) or $rule['protocols'] !== null) {
				$protocols = $rule['protocols'] ?? ["http", "https"];
				if (!in_array($url['scheme'], $protocols)) {
					throw new InputValidationException($input);
				}
			}
		}
		return $data;
	}
}
