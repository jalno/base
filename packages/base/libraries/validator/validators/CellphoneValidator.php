<?php
namespace packages\base\Validator;

use packages\base\{InputValidationException, Options, utility\Safe, Validator\Geo\CountryCodeToRegionCodeMap};

class CellphoneValidator implements IValidator {
	/**
	 * @var string $defaultCountryCode that is default country in ISO 3166-1 alpha-2 format
	 */
	private static $defaultCountryCode = null;

	/**
	 * @return string that is the code of default country in ISO 3166-1 alpha-2 format
	 */
	public static function getDefaultCountryCode(bool $useCache = true): string {
		if (empty(self::$defaultCountryCode) or !$useCache) {
			self::$defaultCountryCode = Options::get("packages.base.validators.default_cellphone_country_code");
		}
		return self::$defaultCountryCode;
	}

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
			return new NullValue;
		}
		if (is_string($data)) {
			if (strpos($data, '.') !== false) {
				$parts = explode('.', $data);
				$code = $parts[0];
				// check if code is numeric, we find the related region code if just one region exists for the code
				if (is_numeric($code)) {
					$relatedCountries = CountryCodeToRegionCodeMap::$CC2RMap[$code] ?? [];
					if ($relatedCountries and count($relatedCountries) == 1) {
						$code = $relatedCountries[0];
					}
				}
				$data = array(
					'code' => $code,
					'number' => $parts[1],
				);
			} else {
				$data = array(
					'code' => '',
					'number' => $data,
				);
			}
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

		if (empty($data['code'])) {
			$data['code'] = Options::get("packages.base.validators.default_cellphone_country_code");
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

		switch ($data['code']) {
			/**
			 * Iran, Islamic Republic Of
			 */
			case 'IR':
				if (!Safe::is_cellphone_ir((substr($data['number'], 0, 2) !== "98" ? "98" : "") . $data['number'])) {
					throw new InputValidationException($input, "not_ir_cellphone");
				}
				$data["number"] = Safe::cellphone_ir($data["number"]);
				break;
		}

		$combinedData = $data['code'] . '.' . $data['number'];
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
			return $combinedOutput ? $combinedData : array(
				'code' => $data['code'],
				'number' => $data['number'],
			);
		}
		return $combinedOutput ? $combinedData : array(
			'code' => $data['code'],
			'number' => $data['number'],
		);
	}
}