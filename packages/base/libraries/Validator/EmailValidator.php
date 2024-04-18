<?php

namespace packages\base\Validator;

use packages\base\InputValidationException;
use packages\base\Utility\Safe;

class EmailValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['email'];
    }

    /**
     * Validate data to be a email.
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
        } elseif (!Safe::is_email($data)) {
            throw new InputValidationException($input);
        }
    }
}
