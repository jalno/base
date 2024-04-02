<?php

namespace packages\base\Validator;

use packages\base\InputValidationException;
use packages\base\utility\safe;

class IPValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['ip4'];
    }

    /**
     * Validate data to be a ipv4.
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
        if (isset($rule['values']) and $rule['values']) {
            if (!in_array($data, $rule['values'])) {
                throw new InputValidationException($input);
            }
        } elseif (!safe::is_ip4($data)) {
            throw new InputValidationException($input);
        }
    }
}
