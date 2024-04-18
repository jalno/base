<?php

namespace packages\base\Validator;

use packages\base\Date;
use packages\base\InputValidationException;
use packages\base\Utility\Safe;

class DateValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['date'];
    }

    /**
     * Validate data to be a cellphone.
     *
     * @return mixed|null new value, if needed
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data)
    {
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

        $date = Safe::is_date($data);
        if (!$date) {
            throw new InputValidationException($input);
        }
        if (isset($rule['unix']) and $rule['unix']) {
            $time = Date::mktime($date['h'] ?? 0, $date['i'] ?? 0, $date['s'] ?? 0, $date['m'], $date['d'], $date['Y']);
            if (!$time) {
                throw new InputValidationException($input);
            }

            return $time;
        }
        $data = "{$date['Y']}/{$date['m']}/{$date['d']}";
        if (isset($date['h'])) {
            $data .= " {$date['h']}";
        }
        if (isset($date['i'])) {
            $data .= ":{$date['i']}";
        }
        if (isset($date['s'])) {
            $data .= ":{$date['s']}";
        }

        return $data;
    }
}
