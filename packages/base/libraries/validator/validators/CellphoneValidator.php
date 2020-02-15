<?php
namespace packages\base\Validator;

use packages\base\{utility\Safe, InputValidationException};

class CellphoneValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['cellphone'];
	}

	/**
	 * Validate data to be a cellphone.
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
			return $data;
		}
		$data = ltrim($data, "+");
		if (!preg_match("/^\d+$/", $data)) {
			throw new InputValidationException($input);
		}
		$code = "";
		if (substr($data, 0, 1) == "0") {
			$code = Options::get("packages.base.validators.default_cellphone_country_code");
			$data = $code . substr($data, 1);
		} else {
			$code = substr($data, 0, 1);
		}
		switch ($code) {
			/**
			 * Iran, Islamic Republic Of
			 */
			case "98":
				if (!Safe::is_cellphone_ir($data)) {
					throw new InputValidationException($input);
				}
				return Safe::cellphone_ir($data);
		}
		return $data;
	}
}
