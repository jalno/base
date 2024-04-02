<?php

namespace packages\base\Validator;

use packages\base\InputValidationException;

class BooleanValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['bool'];
    }

    /**
     * Validate data to be a boolean value.
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

        return !('0' === $data || 'false' === $data || '' === $data);
    }
}
