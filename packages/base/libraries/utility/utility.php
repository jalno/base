<?php

namespace packages\base\utility;

use packages\base\Validator\CellphoneValidator;
use packages\base\Validator\Geo\CountryCodeToRegionCodeMap;

function array_column($input, $column_key, $index_key = null)
{
    if (isset($input[0]) and is_object($input[0])) {
        $return = [];
        foreach ($input as $key => $val) {
            if (isset($val->$column_key)) {
                if ($index_key) {
                    $return[$val->$index_key] = $val->$column_key;
                } else {
                    $return[$key] = $val->$column_key;
                }
            }
        }

        return $return;
    } else {
        return \array_column($input, $column_key, $index_key);
    }
}

/**
 * convert format: 'IR.9387654321' or '+98.9387654321'
 * 		to format: '98.9387654321'.
 *
 * @return string is phone number with dialing code
 */
function getTelephoneWithDialingCode(string $phonenumber): string
{
    $code = null;
    $number = null;
    $dialingCode = null;
    if (false !== strpos($phonenumber, '.')) {
        $exploded = explode('.', $phonenumber);
        $code = $exploded[0];
        $number = $exploded[1];
    } else {
        $number = $phonenumber;
    }
    if (!$number) {
        return '';
    }
    $code = $code ? ltrim($code, '+') : CellphoneValidator::getDefaultCountryCode();
    if (is_numeric($code)) {
        $dialingCode = $code;
    } else {
        $r2c = CountryCodeToRegionCodeMap::regionCodeToCountryCode();
        $dialingCode = isset($r2c[$code]) ? $r2c[$code] : '';
    }

    return $dialingCode.'.'.$number;
}
