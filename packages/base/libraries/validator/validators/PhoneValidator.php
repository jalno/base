<?php

namespace packages\base\Validator;

use packages\base\InputValidationException;
use packages\base\Options;
use packages\base\Validator\Geo\CountryCodeToRegionCodeMap;

class PhoneValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['phone'];
    }

    /**
     * Validate data to be a phone.
     *
     * @return mixed|null new value, if needed
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data)
    {
        if (empty($data)
            or (is_array($data)
            and (
                (array_key_exists('code', $data) and empty($data['code']))
                or (array_key_exists('number', $data) and empty($data['number'])))
            )
        ) {
            if (!isset($rule['empty']) or !$rule['empty']) {
                throw new InputValidationException($input, 'empty_data');
            }
            if (isset($rule['default'])) {
                return $rule['default'];
            }

            return new NullValue();
        }
        if (is_string($data)) {
            if (false !== strpos($data, '.')) {
                $parts = explode('.', $data);
                $code = $parts[0];
                // check if code is numeric, we find the related region code if just one region exists for the code
                if (is_numeric($parts[0])) {
                    $relatedCountries = array_key_exists($parts[0], CountryCodeToRegionCodeMap::$CC2RMap);
                    if (1 == count($relatedCountries)) {
                        $code = $relatedCountries[0];
                    }
                }
                $data = [
                    'code' => $code,
                    'number' => $parts[1],
                ];
            } else {
                $data = [
                    'code' => '',
                    'number' => $data,
                ];
            }
        }
        if (!is_array($data)) {
            throw new InputValidationException($input, 'datatype');
        }
        if (2 != count($data) or !isset($data['code'], $data['number'])) {
            throw new InputValidationException($input, 'bad_data');
        }

        $data = array_map('trim', $data);
        $data['code'] = strtoupper($data['code']);
        $data['number'] = ltrim($data['number'], '0');

        if (empty($data['code'])) { // in case of empty code
            $data['code'] = strval(Options::get('packages.base.validators.default_cellphone_country_code')) ?: 'IR';
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
        $combinedData = $data['code'].'.'.$data['number'];
        $combinedOutput = isset($rule['combined-output']) ? boolval($rule['combined-output']) : true;

        if (isset($rule['values']) and $rule['values'] and is_array($rule['values'])) {
            $found = false;
            foreach ($rule['values'] as $value) {
                if (is_string($value)) {
                    if ($value == $combinedData) {
                        $found = true;
                        break;
                    }
                } elseif (is_array($value) and isset($value['code'], $value['number'])) {
                    if ($value['code'] == $data['code'] and $value['number'] == $data['number']) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                throw new InputValidationException($input, 'invalid_value');
            }

            return $combinedOutput ? $combinedData : [
                'code' => $data['code'],
                'number' => $data['number'],
            ];
        }

        return $combinedOutput ? $combinedData : [
            'code' => $data['code'],
            'number' => $data['number'],
        ];
    }
}
