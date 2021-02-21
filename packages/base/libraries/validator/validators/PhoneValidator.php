<?php
namespace packages\base\Validator;

use packages\base\{InputValidationException, Options, utility\Safe, Validator\Geo\CountryCodeToRegionCodeMap};

class PhoneValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['phone'];
	}

	/**
	 * Validate data to be a phone.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return mixed|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		if (empty($data) or
			(is_array($data) and
			(
				(array_key_exists("code", $data) and empty($data["code"])) or
				(array_key_exists("number", $data) and empty($data["number"])))
			)
		) {
			if (!isset($rule['empty']) or !$rule['empty']) {
				throw new InputValidationException($input, 'empty_data');
			}
			if (isset($rule['default'])) {
				return $rule['default'];
			}
			return new NullValue;
		}
		if (!is_array($data)) {
			throw new InputValidationException($input, 'datatype');
		}
		if (count($data) != 2 or !isset($data['code'], $data['number'])) {
			throw new InputValidationException($input, 'bad_data');
		}

		$data = array_map('trim', $data);
		$data['code'] = ltrim($data['code'], '+');
		$data['number'] = ltrim($data['number'], '0');
		$combinedData = $data['code'] . $data['number'];

		if (empty($data['code'])) {
			$data['code'] = strval(Options::get("packages.base.validators.default_cellphone_country_code"));
		}
		if (!is_numeric($data['code'])) {
			throw new InputValidationException($input, 'bad_code_datatype');
		}
		if (!is_numeric($data['number'])) {
			throw new InputValidationException($input, 'bad_number_datatype');
		}

		$combinedOutput = isset($rule['combined-output']) ? boolval($rule['combined-output']) : true;

		if (isset($rule['values']) and $rule['values'] and is_array($rule['values'])) {
			$found = false;
			foreach ($rule['values'] as $value) {
				if (is_string($value)) {
					if ($value == $combinedData) {
						$found = true;
						break;
					}
				} else if (is_array($value) and isset($value['code'], $value['number'])) {
					if ($value['code'] == $data['code'] and $value['number'] == $data['number']) {
						$found = true;
						break;
					}
				}
			}
			if (!$found) {
				throw new InputValidationException($input, 'invalid_value');
			}
			return $combinedOutput ? $data['code'] . '.' . $data['number'] : $data;
		}

		if (!array_key_exists($data['code'], CountryCodeToRegionCodeMap::$CC2RMap)) {
			throw new InputValidationException($input, 'invalid_code');
		}

		return $combinedOutput ? $data['code'] . '.' . $data['number'] : $data;;
	}
}