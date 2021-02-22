<?php
namespace packages\base\Validator;

use packages\base\{InputValidationException, Options, utility\Safe, Validator\Geo\CountryCodeToRegionCodeMap};

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
	 * @param array $data that should have 'code' and 'number' index, ex: array(
	 * 				[code]: 'IR',
	 * 				[number]: '9131104625
	 * )
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
			return;
		}
		if (!is_array($data)) {
			throw new InputValidationException($input, 'datatype');
		}
		if (count($data) != 2 or !isset($data['code'], $data['number'])) {
			throw new InputValidationException($input, 'bad_data');
		}

		$data = array_map('trim', $data);
		$data['code'] = strtoupper($data['code']);
		$data['number'] = ltrim($data['number'], '0');

		if (empty($data['code'])) { // in case of empty code
			$data['code'] = strval(Options::get("packages.base.validators.default_cellphone_country_code")) ?: 'IR';
		}
		if (!is_string($data['code'])) {
			throw new InputValidationException($input, 'bad_code_datatype');
		}
		if (!is_numeric($data['number'])) {
			throw new InputValidationException($input, 'bad_number_datatype');
		}

		$regionCodeToCountryCode = CountryCodeToRegionCodeMap::regionCodeToCountryCode();
		if (!array_key_exists($data['code'], $regionCodeToCountryCode)) {
			throw new InputValidationException($input, 'invalid_code');
		}
		$combinedData = $regionCodeToCountryCode[$data['code']] . '.' . $data['number'];
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

		switch ($data['code']) {
			/**
			 * Iran, Islamic Republic Of
			 */
			case 'IR':
				if (!Safe::is_cellphone_ir($regionCodeToCountryCode[$data['code']] . $data['number'])) {
					throw new InputValidationException($input, "not_ir_cellphone");
				}
		}
		return $combinedOutput ? $regionCodeToCountryCode[$data['code']] . '.' . $data['number'] : array(
			'code' => $data['code'],
			'number' => $data['number'],
			'dialingCode' => $regionCodeToCountryCode[$data['code']],
		);
	}
}